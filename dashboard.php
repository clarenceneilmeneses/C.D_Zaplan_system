<?php
/* Client dashboard: their bookings + the "New Rental Request" form (with map pin for delivery distance). */
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_role('client');

$bookErrors = [];
if (is_post()) {
  csrf_check();
  $action = $_POST['action'] ?? '';

  if ($action === 'cancel') {
    $bid = (int)$_POST['booking_id'];
    $done = q('UPDATE bookings SET status = "Cancelled" WHERE id = ? AND user_id = ? AND status = "Pending Review"',
              [$bid, $me['id']])->rowCount();
    if ($done) notify_admins('cancel', booking_ref($bid) . ' cancelled by the client', $me['full_name'] . ' cancelled their request.', "booking.php?open=$bid");
    flash($done ? 'success' : 'error', $done ? 'Your request was cancelled.' : 'That booking can no longer be cancelled. Please call us.');
    redirect(url_with());
  }

  if ($action === 'book') {
    $type  = equipment_type((int)($_POST['type_id'] ?? 0));
    $name  = trim($_POST['contact_name'] ?? '');
    $phone = trim($_POST['contact_phone'] ?? '');
    $addr  = trim($_POST['site_address'] ?? '');
    $start = $_POST['start_date'] ?? '';
    $end   = $_POST['end_date'] ?? '';
    $lat   = $_POST['lat'] ?? '';
    $lng   = $_POST['lng'] ?? '';
    $validDate = fn($d) => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

    if (!$type)                                         $bookErrors[] = 'Please choose the equipment.';
    if ($name === '')                                   $bookErrors[] = 'Please enter the contact name.';
    if (!preg_match('/^[0-9+\-\s()]{7,30}$/', $phone))  $bookErrors[] = 'Please enter a valid contact number.';
    if ($addr === '')                                   $bookErrors[] = 'Please enter the project site address.';
    if (!is_numeric($lat) || !is_numeric($lng))         $bookErrors[] = 'Please pin your project site on the map so we can compute the delivery fee.';
    if (!$validDate($start) || !$validDate($end))       $bookErrors[] = 'Please choose your rental dates.';
    elseif ($start < date('Y-m-d'))                     $bookErrors[] = 'The rental start date cannot be in the past.';
    elseif ($end < $start)                              $bookErrors[] = 'The rental end date must be on or after the start date.';
    elseif ((strtotime($end) - strtotime($start)) / 86400 >= 365) $bookErrors[] = 'Rentals longer than a year need a custom quote. Please send us an inquiry.';

    if (!$bookErrors) {
      try {
        $idFile = store_valid_id($_FILES['valid_id'] ?? []);
      } catch (RuntimeException $ex) {
        $bookErrors[] = $ex->getMessage();
      }
    }

    if (!$bookErrors) {
      $days = (int)round((strtotime($end) - strtotime($start)) / 86400) + 1;
      $km   = trusted_distance((float)$lat, (float)$lng, $_POST['km'] ?? null);
      $cost = estimate_cost($type, $km, $days);
      q('INSERT INTO bookings (user_id, contact_name, contact_phone, type_id, site_address, site_lat, site_lng, distance_km,
                               start_date, end_date, rental_amount, mobilization_fee, total_amount, valid_id_path)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$me['id'], $name, $phone, $type['id'], $addr, (float)$lat, (float)$lng, $km,
         $start, $end, $cost['rental'], $cost['mobilization'], $cost['total'], $idFile]);
      $newId = (int)db()->lastInsertId();
      $ref = booking_ref($newId);
      notify_admins('booking', "New booking request $ref", "$name · {$type['name']} · " . date_range($start, $end) . ' · ' . money($cost['total']), "booking.php?open=$newId");
      if (!$me['phone']) q('UPDATE users SET phone = ? WHERE id = ?', [$phone, $me['id']]);   // remember for next time
      flash('success', "Request $ref submitted. Estimated total " . money($cost['total']) . '. We\'ll review it and confirm your deposit.');
      redirect('dashboard.php');
    }
  }
}

// My Bookings table: search box + global date range (rentals that overlap it), 10 per page
$search = search_term();
[$w, $p] = where(['b.user_id = ?', [$me['id']]],
                 like_any(['CONCAT("BK-", b.id)', 't.name', 'b.site_address'], $search),
                 date_overlap('b.start_date', 'b.end_date'));
$list = paginate("SELECT b.*, t.name AS equipment FROM bookings b JOIN equipment_types t ON t.id = b.type_id
                  $w ORDER BY b.start_date DESC, b.id DESC", $p);
$bookings = $list['rows'];

// Stat cards: what's on site / pending right now, and money paid within the date range
$today   = date('Y-m-d');
$active  = q('SELECT site_address FROM bookings WHERE user_id = ? AND status = "Confirmed" AND start_date <= ? AND end_date >= ?
              ORDER BY start_date', [$me['id'], $today, $today])->fetchAll(PDO::FETCH_COLUMN);
$pending = (int)q('SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = "Pending Review"', [$me['id']])->fetchColumn();
[$w, $p] = where(['b.user_id = ? AND i.status = "Paid"', [$me['id']]], date_where('i.paid_on'));
$paid    = q("SELECT COALESCE(SUM(i.amount), 0) AS total, COUNT(*) AS n FROM invoices i JOIN bookings b ON b.id = i.booking_id $w", $p)->fetch();
$billed  = (int)$paid['n'];

$stats = [
  ['Active Projects',  count($active),               $active ? $active[0] . ' · On site now' : 'No equipment on site today', 'lucide:truck'],
  ['Pending Requests', $pending,                     'Awaiting admin review',                                                 'lucide:clock'],
  ['Total Spent',      money_short($paid['total']),  'Paid across ' . $billed . ' booking' . ($billed === 1 ? '' : 's') . ' · ' . date_filter()['label'], 'lucide:banknote'],
];

// Prefill the booking form: from a failed submit, or from the landing-page estimator (?book=1&type=..&lat=..)
$src = $bookErrors ? $_POST : $_GET;
$prefill = [
  'type'  => (int)($src['type_id'] ?? $src['type'] ?? 0),
  'lat'   => is_numeric($src['lat'] ?? null) ? (float)$src['lat'] : null,
  'lng'   => is_numeric($src['lng'] ?? null) ? (float)$src['lng'] : null,
  'addr'  => (string)($src['site_address'] ?? $src['addr'] ?? ''),
  'start' => (string)($src['start_date'] ?? ''),
  'end'   => (string)($src['end_date'] ?? ''),
  'days'  => max(1, (int)($src['days'] ?? 1)),
];
$openBooking = $bookErrors || isset($_GET['book']);

$pageTitle = 'Client Dashboard | C.D. Zaplan Trucking';
$portal = 'client'; $active = 'bookings';
$useMap = true;
$dateFilter = true;
require_once __DIR__ . '/includes/portal-header.php';
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Welcome back, <?= e(explode(' ', $me['full_name'])[0]) ?>!</h1>
    <p class="page-sub">Track your active equipment deployments and manage bookings.</p>
  </div>
  <button onclick="openBooking()" class="btn btn--amber">
    <iconify-icon icon="lucide:plus" width="16"></iconify-icon> Book Equipment
  </button>
</div>

<div class="stat-grid">
  <?php foreach ($stats as [$l,$v,$s,$i]): ?>
  <div class="stat-card">
    <div class="stat-card__head"><span><?= $l ?></span><iconify-icon icon="<?= $i ?>" width="16" class="stat-card__icon"></iconify-icon></div>
    <div class="stat-card__value"><?= e($v) ?></div>
    <div class="stat-card__sub"><?= e($s) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<section class="card card--spaced">
  <div class="card__head"><h2 class="card__title">My Bookings</h2></div>
  <div class="toolbar toolbar--flush"><?= list_search('Search ref, equipment or site…') ?></div>
  <div class="table-wrap">
    <table class="table table--min-720">
      <thead>
        <tr><th>Ref ID</th><th>Equipment</th><th>Location</th><th>Schedule</th><th>Status</th><th>Cost</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
        <tr>
          <td class="td-strong"><?= booking_ref($b['id']) ?></td>
          <td><?= e($b['equipment']) ?></td>
          <td class="td-muted"><?= e($b['site_address']) ?><?php if ($b['distance_km'] !== null): ?><div class="cell-meta"><?= e($b['distance_km']) ?> km from yard</div><?php endif; ?></td>
          <td class="td-muted"><?= date_range($b['start_date'], $b['end_date']) ?></td>
          <td><?= pill($b['status']) ?></td>
          <td class="td-strong"><?= money($b['total_amount']) ?></td>
          <td>
            <?php if ($b['status'] === 'Pending Review'): ?>
            <form method="post" onsubmit="return confirm('Cancel request <?= booking_ref($b['id']) ?>?')">
              <?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
              <button name="action" value="cancel" class="btn btn--ghost btn--sm">Cancel</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$bookings): ?>
        <tr><td colspan="7" class="empty"><?= ($search !== '' || date_filter()['from'])
          ? 'No bookings match your filters.'
          : 'No bookings yet. Click <strong>Book Equipment</strong> to send your first request.' ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pagination($list, 'bookings') ?>
</section>

<!-- New rental request modal: collects everything the client requires at booking -->
<div id="bookingModal" class="modal hidden" onclick="if (event.target === this) closeModal('bookingModal')">
  <div class="dialog dialog--padded dialog--wide">
    <div class="dialog__head">
      <h2 class="dialog__title">New Rental Request</h2>
      <button onclick="closeModal('bookingModal')" aria-label="Close" class="close-btn close-btn--plain"><iconify-icon icon="lucide:x" width="20"></iconify-icon></button>
    </div>
    <form id="bookingForm" method="post" enctype="multipart/form-data" class="form form--modal">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="book">
      <input type="hidden" name="lat" value="<?= e($prefill['lat']) ?>">
      <input type="hidden" name="lng" value="<?= e($prefill['lng']) ?>">
      <input type="hidden" name="km">

      <?php foreach ($bookErrors as $err): ?>
        <div class="alert alert--error"><iconify-icon icon="lucide:circle-alert" width="16"></iconify-icon><span><?= e($err) ?></span></div>
      <?php endforeach; ?>

      <div class="form-grid form-grid--two">
        <div><label class="label" for="bkName">Full Name</label><input id="bkName" name="contact_name" type="text" value="<?= e($_POST['contact_name'] ?? $me['full_name']) ?>" required class="field"></div>
        <div><label class="label" for="bkPhone">Contact Details</label><input id="bkPhone" name="contact_phone" type="tel" value="<?= e($_POST['contact_phone'] ?? $me['phone']) ?>" placeholder="Mobile number" required class="field"></div>
      </div>
      <div><label class="label" for="bkType">Equipment</label>
        <select id="bkType" name="type_id" class="field">
          <?php foreach (equipment_types() as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= $prefill['type'] === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?> · <?= money($t['daily_rate'], 0) ?>/day</option>
          <?php endforeach; ?>
        </select></div>
      <div>
        <label class="label" for="bkAddr">Project Site Address <span class="form-hint form-hint--inline">pick a suggestion or click the map</span></label>
        <div><input id="bkAddr" name="site_address" type="text" value="<?= e($prefill['addr']) ?>" placeholder="Start typing a town, barangay or landmark…" required maxlength="255" class="field"></div>
      </div>
      <div id="bookingMap" class="booking-map" aria-label="Map. Click to pin your project site."></div>
      <div class="form-grid form-grid--dates">
        <div><label class="label" for="bkStart">Rental Start</label><input id="bkStart" name="start_date" type="date" min="<?= date('Y-m-d') ?>" value="<?= e($prefill['start']) ?>" required class="field"></div>
        <div><label class="label" for="bkEnd">Rental End</label><input id="bkEnd" name="end_date" type="date" min="<?= date('Y-m-d') ?>" value="<?= e($prefill['end']) ?>" required class="field"></div>
      </div>
      <div><label class="label" for="bkId">Valid ID</label>
        <input id="bkId" name="valid_id" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required class="field field--file">
        <p class="form-hint">JPG, PNG or PDF, up to 5 MB.</p></div>

      <div class="estimate-strip">
        <div><span>Distance</span><strong id="bkKm">—</strong></div>
        <div><span>Rental</span><strong id="bkRental">—</strong></div>
        <div><span>Delivery &amp; pull-out</span><strong id="bkMob">—</strong></div>
        <div><span>Estimated total</span><strong id="bkTotal" class="estimate-strip__total">—</strong></div>
      </div>

      <div class="notice">
        <div class="notice__row"><iconify-icon icon="lucide:badge-check" width="14" class="notice__icon"></iconify-icon> A down payment or security deposit is required before your booking is confirmed.</div>
        <div class="notice__row"><iconify-icon icon="lucide:route" width="14" class="notice__icon"></iconify-icon> Delivery / mobilization fees are charged per distance and trip.</div>
        <div class="notice__row"><iconify-icon icon="lucide:banknote" width="14" class="notice__icon"></iconify-icon> Payment is accepted in cash on delivery / on site.</div>
      </div>
      <button type="submit" class="btn btn--amber btn--block">Submit Request</button>
    </form>
  </div>
</div>

<script>window.ESTIMATOR = <?= js(estimator_config()) ?>;</script>
<script src="assets/js/estimator.js"></script>
<script>
  const bk = { form: document.getElementById('bookingForm'), picker: null, site: null };
  const prefill = <?= js($prefill) ?>;

  // Coming from the landing-page estimator with a day count: pre-fill an end date once a start date is picked
  if (prefill.days > 1 && !prefill.end) {
    document.getElementById('bkStart').addEventListener('change', e => {
      const end = document.getElementById('bkEnd');
      if (!end.value && e.target.value) {
        const d = new Date(e.target.value + 'T00:00:00Z'); d.setUTCDate(d.getUTCDate() + prefill.days - 1);   // UTC so the date doesn't shift in UTC+8
        end.value = d.toISOString().slice(0, 10);
        renderBookingEstimate();
      }
    }, { once: true });
  }

  function openBooking() {
    openModal('bookingModal');
    if (!bk.picker) {
      const initial = prefill.lat !== null ? { lat: prefill.lat, lng: prefill.lng, address: prefill.addr } : null;
      bk.picker = new SitePicker(document.getElementById('bookingMap'), ESTIMATOR, site => {
        bk.site = site;
        bk.form.lat.value = site.lat.toFixed(6);
        bk.form.lng.value = site.lng.toFixed(6);
        bk.form.km.value = site.km !== null ? site.km.toFixed(2) : '';
        const addr = document.getElementById('bkAddr');
        if (site.address && (!addr.value || addr.dataset.auto === '1')) { addr.value = site.address; addr.dataset.auto = '1'; }
        renderBookingEstimate();
      }, initial);
      document.getElementById('bkAddr').addEventListener('input', e => { e.target.dataset.auto = '0'; });
      // Address suggestions: picking one pins it on the map and measures the delivery distance
      new PlaceAutocomplete(document.getElementById('bkAddr'), place => {
        document.getElementById('bkAddr').dataset.auto = '0';
        bk.picker.setSite(place.lat, place.lng, place.label);
      }, { near: [ESTIMATOR.base.lat, ESTIMATOR.base.lng] });
    }
    setTimeout(() => bk.picker.refresh(), 50);
  }

  function renderBookingEstimate() {
    const $ = id => document.getElementById(id);
    const type = ESTIMATOR.types.find(t => t.id === +bk.form.type_id.value);
    const s = bk.form.start_date.value, e = bk.form.end_date.value;
    const days = s && e && e >= s ? Math.round((new Date(e) - new Date(s)) / 86400000) + 1 : null;
    const km = bk.site && !bk.site.pending ? bk.site.km : null;
    $('bkKm').textContent = km === null ? (bk.site ? 'Measuring…' : 'Pin the site') : km.toFixed(1) + ' km' + (bk.site.approx ? ' (approx.)' : '');
    if (km === null) { $('bkRental').textContent = $('bkMob').textContent = $('bkTotal').textContent = '—'; return; }
    const c = estimateCost(type, km, days || 1);
    $('bkMob').textContent = peso(c.mobilization);
    $('bkRental').textContent = days ? peso(c.rental) : 'Pick dates';
    $('bkTotal').textContent = days ? peso(c.total) : '—';
  }
  ['type_id', 'start_date', 'end_date'].forEach(n => bk.form[n].addEventListener('change', renderBookingEstimate));
  bk.form.start_date.addEventListener('change', () => { bk.form.end_date.min = bk.form.start_date.value; });

  bk.form.addEventListener('submit', e => {
    if (!bk.form.lat.value) { e.preventDefault(); alert('Please click the map to pin your project site.'); }
  });

  <?php if ($openBooking): ?>document.addEventListener('DOMContentLoaded', openBooking);<?php endif; ?>
</script>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
