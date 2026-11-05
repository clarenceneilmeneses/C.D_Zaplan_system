<?php
/*
 * Admin: rental bookings.
 * Flow: client sends request (Pending Review) → admin collects deposit, assigns a unit and approves (Confirmed + invoice created)
 *       → job finishes (Completed, unit back to Available) → invoice marked Paid when the balance is collected on site.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

/** Units of a type that are not under maintenance and not booked on overlapping dates */
function free_units(int $typeId, string $start, string $end, int $exceptBooking = 0): array {
  return q('SELECT e.* FROM equipment e
            WHERE e.type_id = ? AND e.status <> "Maintenance"
              AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.equipment_id = e.id AND b.status = "Confirmed"
                              AND b.id <> ? AND b.start_date <= ? AND b.end_date >= ?)
            ORDER BY e.asset_code', [$typeId, $exceptBooking, $end, $start])->fetchAll();
}

if (is_post()) {
  csrf_check();
  $id = (int)($_POST['booking_id'] ?? 0);
  $b = q('SELECT * FROM bookings WHERE id = ?', [$id])->fetch();
  if (!$b) { flash('error', 'Booking not found.'); redirect('booking.php'); }
  $ref = booking_ref($id);
  $today = date('Y-m-d');
  $client = (int)$b['user_id'];
  $typeName = equipment_type((int)$b['type_id'])['name'] ?? 'equipment';

  switch ($_POST['action'] ?? '') {
    case 'approve':
      $unitId = (int)($_POST['equipment_id'] ?? 0);
      $ok = array_filter(free_units((int)$b['type_id'], $b['start_date'], $b['end_date'], $id), fn($u) => (int)$u['id'] === $unitId);
      if ($b['status'] !== 'Pending Review') { flash('error', "$ref is no longer pending."); break; }
      if (!$ok)                              { flash('error', 'Choose a unit that is free for those dates.'); break; }
      if (empty($_POST['deposit_received'])) { flash('error', 'Confirm that the deposit was received before approving.'); break; }

      db()->beginTransaction();
      q('UPDATE bookings SET status = "Confirmed", deposit_status = "Paid", equipment_id = ? WHERE id = ?', [$unitId, $id]);
      $year = date('Y');
      $seq = (int)q('SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(invoice_no, "-", -1) AS UNSIGNED)), 0) FROM invoices WHERE invoice_no LIKE ?', ["INV-$year-%"])->fetchColumn() + 1;
      $invNo = sprintf('INV-%s-%03d', $year, $seq);
      q('INSERT INTO invoices (invoice_no, booking_id, issued_on, due_on, amount) VALUES (?, ?, ?, ?, ?)',
        [$invNo, $id, $today, date('Y-m-d', strtotime('+14 days')), $b['total_amount']]);
      if ($b['start_date'] <= $today) set_equipment_status($unitId, 'Deployed', "Dispatched to {$b['site_address']} ($ref)");
      db()->commit();
      notify_client($client, 'notify_booking', 'booking', "$ref is confirmed",
        "Your $typeName is booked for " . date_range($b['start_date'], $b['end_date']) . '. Deposit received, thank you!', 'dashboard.php?q=' . urlencode($ref));
      notify_client($client, 'notify_invoice', 'invoice', "Invoice $invNo issued",
        money($b['total_amount']) . ' due ' . fdate(date('Y-m-d', strtotime('+14 days'))) . '. Payment is cash on delivery / on site.', 'invoice.php?no=' . urlencode($invNo));
      flash('success', "$ref confirmed. Invoice $invNo was created.");
      break;

    case 'dispatch':
      if ($b['status'] === 'Confirmed' && $b['equipment_id']) {
        set_equipment_status((int)$b['equipment_id'], 'Deployed', "Dispatched to {$b['site_address']} ($ref)");
        notify_client($client, 'notify_booking', 'dispatch', "Your $typeName is on its way", "The unit for $ref was dispatched to {$b['site_address']}.", 'dashboard.php?q=' . urlencode($ref));
        flash('success', "Unit marked as deployed for $ref.");
      }
      break;

    case 'complete':
      if ($b['status'] !== 'Confirmed') { flash('error', "Only confirmed bookings can be completed."); break; }
      q('UPDATE bookings SET status = "Completed" WHERE id = ?', [$id]);
      if ($b['equipment_id']) set_equipment_status((int)$b['equipment_id'], 'Available', "Returned from {$b['site_address']} ($ref)");
      notify_client($client, 'notify_booking', 'booking', "$ref is completed", "Thanks for renting with C.D. Zaplan Trucking! The $typeName has been pulled out of your site.", 'dashboard.php?q=' . urlencode($ref));
      flash('success', "$ref marked as completed.");
      break;

    case 'cancel':
      if (!in_array($b['status'], ['Pending Review', 'Confirmed'], true)) { flash('error', "$ref can't be cancelled."); break; }
      q('UPDATE bookings SET status = "Cancelled" WHERE id = ?', [$id]);
      q('DELETE FROM invoices WHERE booking_id = ? AND status = "Pending"', [$id]);
      if ($b['equipment_id']) set_equipment_status((int)$b['equipment_id'], 'Available', "Booking $ref cancelled");
      notify_client($client, 'notify_booking', 'cancel', "$ref was cancelled", 'Please call us at 09176757888 if you have questions about this booking.', 'dashboard.php?q=' . urlencode($ref));
      flash('success', "$ref was cancelled.");
      break;

    case 'invoice_paid':
      $n = q('UPDATE invoices SET status = "Paid", paid_on = ? WHERE booking_id = ? AND status = "Pending"', [$today, $id])->rowCount();
      if ($n) {
        $invNo = q('SELECT invoice_no FROM invoices WHERE booking_id = ?', [$id])->fetchColumn();
        notify_client($client, 'notify_invoice', 'payment', "Payment received for $ref", "Your official receipt $invNo is ready to view or print.", 'invoice.php?no=' . urlencode($invNo));
      }
      flash($n ? 'success' : 'error', $n ? "Payment recorded for $ref." : 'No unpaid invoice for this booking.');
      break;
  }
  redirect(url_with(['open' => null]));   // back to the same filters and page
}

$statuses = ['Pending Review', 'Confirmed', 'Completed', 'Cancelled'];
$filter = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$search = search_term();

// Filters: search box + global date range (rentals that overlap it) + status chip
$select = 'SELECT b.*, t.name AS equipment, u.full_name AS client, u.email AS client_email,
                  eq.asset_code, eq.status AS unit_status, i.invoice_no, i.status AS invoice_status, i.due_on';
$from = 'FROM bookings b
         JOIN equipment_types t ON t.id = b.type_id
         JOIN users u ON u.id = b.user_id
         LEFT JOIN equipment eq ON eq.id = b.equipment_id
         LEFT JOIN invoices i ON i.booking_id = b.id';
$searchCond = like_any(['CONCAT("BK-", b.id)', 'u.full_name', 'b.contact_name', 'b.contact_phone', 'b.site_address', 't.name', 'eq.asset_code', 'i.invoice_no'], $search);
$rangeCond = date_overlap('b.start_date', 'b.end_date');
[$w, $p] = where($searchCond, $rangeCond, $filter ? ['b.status = ?', [$filter]] : ['', []]);
$list = paginate("$select $from $w ORDER BY FIELD(b.status, 'Pending Review', 'Confirmed', 'Completed', 'Cancelled'), b.start_date DESC, b.id DESC", $p);
$bookings = $list['rows'];
[$w, $p] = where($searchCond, $rangeCond);
$counts = q("SELECT b.status, COUNT(*) $from $w GROUP BY b.status", $p)->fetchAll(PDO::FETCH_KEY_PAIR);

// ?open=ID (from a notification or a search result) opens that booking's drawer, even if it's on another page
$openId = (int)($_GET['open'] ?? 0);
$drawerRows = $bookings;
if ($openId && !in_array($openId, array_map('intval', array_column($bookings, 'id')), true)) {
  if ($extra = q("$select $from WHERE b.id = ?", [$openId])->fetch()) $drawerRows[] = $extra;
  else $openId = 0;
}

$pageTitle = 'Rental Bookings | C.D. Zaplan Trucking';
$portal = 'admin'; $active = 'bookings';
$dateFilter = true;
require_once __DIR__ . '/includes/portal-header.php';
?>
<h1 class="page-title">Rental Bookings</h1>
<p class="page-sub page-sub--spaced">Review requests, confirm deposits, and assign dispatch dates.</p>

<section class="card">
  <div class="toolbar">
    <?= list_search('Search ref, client, site, unit…') ?>
    <div class="chip-row chip-row--scroll">
      <a href="<?= e(url_with(['status' => null, 'page' => null])) ?>" class="chip<?= $filter === '' ? ' is-active' : '' ?>">All <span><?= array_sum($counts) ?></span></a>
      <?php foreach ($statuses as $s): ?>
      <a href="<?= e(url_with(['status' => $s, 'page' => null])) ?>" class="chip<?= $filter === $s ? ' is-active' : '' ?>"><?= $s ?> <span><?= (int)($counts[$s] ?? 0) ?></span></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table table--min-980">
      <thead>
        <tr><th>Ref ID</th><th>Client</th><th>Equipment</th><th>Project Site</th><th>Rental Start / End</th><th>Est. Amount</th><th>Deposit</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $b): $ref = booking_ref($b['id']); ?>
        <tr>
          <td class="td-strong"><?= $ref ?></td>
          <td><div class="person">
            <span class="avatar"><?= e(initials($b['client'])) ?></span>
            <div><div class="cell-title"><?= e($b['client']) ?></div><div class="cell-meta"><?= e($b['contact_phone']) ?> · <?= $b['valid_id_path'] ? 'Valid ID on file' : 'No ID uploaded' ?></div></div></div></td>
          <td><?= e($b['equipment']) ?><?php if ($b['asset_code']): ?><div class="cell-meta"><?= e($b['asset_code']) ?></div><?php endif; ?></td>
          <td class="td-muted"><?= e($b['site_address']) ?><?php if ($b['distance_km'] !== null): ?><div class="cell-meta"><?= e($b['distance_km']) ?> km</div><?php endif; ?></td>
          <td class="td-muted"><?= date_range($b['start_date'], $b['end_date']) ?></td>
          <td class="td-strong"><?= money($b['total_amount']) ?></td>
          <td><?= pill($b['deposit_status']) ?></td>
          <td><?= pill($b['status']) ?></td>
          <td><div class="row-actions">
            <button type="button" onclick="openBookingDrawer(<?= (int)$b['id'] ?>)" class="btn btn--sm <?= $b['status'] === 'Pending Review' ? 'btn--amber' : 'btn--line' ?>">
              <?= $b['status'] === 'Pending Review' ? 'Review' : 'View' ?>
            </button></div></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$bookings): ?>
        <tr><td colspan="9" class="empty">No bookings<?= $filter ? ' with status "' . e($filter) . '"' : '' ?><?= $search !== '' ? ' match "' . e($search) . '"' : '' ?><?= date_filter()['from'] ? ' in ' . e(date_filter()['label']) : '' ?>.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pagination($list, 'bookings') ?>
  <div class="card__foot">
    A down payment or security deposit is required before a booking is confirmed. Delivery / mobilization fees are charged per distance and trip. Payment is cash on delivery / on site.
  </div>
</section>

<!-- One hidden detail panel per booking; the drawer shows the one you click -->
<?php foreach ($drawerRows as $b): $ref = booking_ref($b['id']); $days = (int)round((strtotime($b['end_date']) - strtotime($b['start_date'])) / 86400) + 1; ?>
<template id="booking-<?= (int)$b['id'] ?>">
  <div class="drawer__head">
    <div>
      <h2 class="drawer__title">Booking <?= $ref ?></h2>
      <div class="drawer__meta"><?= pill($b['status']) ?><span class="drawer__date">Requested <?= fdate($b['created_at'], 'M d, Y · g:i A') ?></span></div>
    </div>
    <button onclick="closeDrawer()" aria-label="Close" class="close-btn"><iconify-icon icon="lucide:x" width="22"></iconify-icon></button>
  </div>
  <div class="drawer__body">
    <div>
      <div class="eyebrow eyebrow--muted drawer__label">Client</div>
      <dl class="detail-list">
        <div class="detail"><dt>Name</dt><dd><?= e($b['contact_name']) ?> <span class="cell-meta">(account: <?= e($b['client']) ?>)</span></dd></div>
        <div class="detail"><dt>Phone</dt><dd><a class="detail__link" href="tel:<?= e($b['contact_phone']) ?>"><?= e($b['contact_phone']) ?></a></dd></div>
        <div class="detail"><dt>Email</dt><dd><a class="detail__link" href="mailto:<?= e($b['client_email']) ?>"><?= e($b['client_email']) ?></a></dd></div>
        <div class="detail"><dt>Valid ID</dt><dd><?= $b['valid_id_path'] ? '<a class="detail__link" target="_blank" href="id-file.php?booking=' . (int)$b['id'] . '">Open uploaded ID</a>' : 'Not on file' ?></dd></div>
      </dl>
    </div>
    <div>
      <div class="eyebrow eyebrow--muted drawer__label">Rental</div>
      <dl class="detail-list">
        <div class="detail"><dt>Unit</dt><dd><?= e($b['equipment']) ?><?= $b['asset_code'] ? ' · ' . e($b['asset_code']) . ' (' . e($b['unit_status']) . ')' : '' ?></dd></div>
        <div class="detail"><dt>Dates</dt><dd><?= date_range($b['start_date'], $b['end_date']) ?> · <?= $days ?> day<?= $days === 1 ? '' : 's' ?></dd></div>
        <div class="detail"><dt>Site</dt><dd><?= e($b['site_address']) ?>
          <?php if ($b['site_lat'] !== null): ?><br><a class="detail__link" target="_blank" rel="noopener" href="https://www.openstreetmap.org/directions?route=<?= e(setting('base_lat') . ',' . setting('base_lng') . ';' . $b['site_lat'] . ',' . $b['site_lng']) ?>">Open route map · <?= e($b['distance_km']) ?> km</a><?php endif; ?></dd></div>
      </dl>
    </div>
    <div>
      <div class="eyebrow eyebrow--muted drawer__label">Amount</div>
      <dl class="detail-list">
        <div class="detail"><dt>Rental</dt><dd><?= money($b['rental_amount']) ?></dd></div>
        <div class="detail"><dt>Mobilization</dt><dd><?= money($b['mobilization_fee']) ?></dd></div>
        <div class="detail"><dt>Total</dt><dd class="strong"><?= money($b['total_amount']) ?></dd></div>
        <div class="detail"><dt>Invoice</dt><dd><?= $b['invoice_no']
          ? '<a class="detail__link" href="invoice.php?no=' . urlencode($b['invoice_no']) . '">' . e($b['invoice_no']) . '</a> ' . pill(invoice_status(['status' => $b['invoice_status'], 'due_on' => $b['due_on']]))
          : 'Created on approval' ?></dd></div>
      </dl>
    </div>

    <?php if ($b['status'] === 'Pending Review'): $units = free_units((int)$b['type_id'], $b['start_date'], $b['end_date'], (int)$b['id']); ?>
    <form method="post" class="form drawer__form">
      <?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
      <div class="eyebrow eyebrow--muted">Approve this request</div>
      <?php if ($units): ?>
      <div>
        <label class="label">Assign unit</label>
        <select name="equipment_id" class="field" required>
          <?php foreach ($units as $u): ?><option value="<?= (int)$u['id'] ?>"><?= e($u['asset_code'] . ' · ' . $u['model'] . ($u['plate_no'] ? ' · ' . $u['plate_no'] : '')) ?></option><?php endforeach; ?>
        </select>
      </div>
      <label class="check"><input type="checkbox" name="deposit_received" value="1" required> Deposit / down payment received</label>
      <button name="action" value="approve" class="btn btn--amber">Approve &amp; create invoice</button>
      <?php else: ?>
      <p class="alert alert--error">No <?= e($b['equipment']) ?> is free for these dates. Free up a unit on the Fleet page or ask the client to move the dates.</p>
      <?php endif; ?>
    </form>
    <?php endif; ?>
  </div>

  <form method="post" class="drawer__foot">
    <?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
    <?php if ($b['status'] === 'Confirmed' && $b['unit_status'] !== 'Deployed'): ?>
      <button name="action" value="dispatch" class="btn btn--outline">Mark unit dispatched</button>
    <?php endif; ?>
    <?php if ($b['status'] === 'Confirmed'): ?>
      <button name="action" value="complete" class="btn btn--outline" onclick="return confirm('Mark <?= $ref ?> as completed? The unit goes back to Available.')">Mark completed</button>
    <?php endif; ?>
    <?php if ($b['invoice_status'] === 'Pending'): ?>
      <button name="action" value="invoice_paid" class="btn btn--amber">Record payment</button>
    <?php endif; ?>
    <?php if (in_array($b['status'], ['Pending Review', 'Confirmed'], true)): ?>
      <button name="action" value="cancel" class="btn btn--ghost" onclick="return confirm('Cancel <?= $ref ?>?')">Cancel booking</button>
    <?php endif; ?>
    <a href="mailto:<?= e($b['client_email']) ?>?subject=<?= rawurlencode("Your booking $ref") ?>" class="btn btn--ghost"><iconify-icon icon="lucide:mail" width="16"></iconify-icon> Email client</a>
  </form>
</template>
<?php endforeach; ?>

<div id="bookingDrawer" class="overlay overlay-right" role="dialog" aria-modal="true" onclick="if (event.target === this) closeDrawer()">
  <aside class="sheet drawer" id="bookingDrawerBody"></aside>
</div>

<script>
function openBookingDrawer(id) {
  const body = document.getElementById('bookingDrawerBody');
  body.replaceChildren(document.getElementById('booking-' + id).content.cloneNode(true));
  document.getElementById('bookingDrawer').classList.add('is-open');
  document.body.style.overflow = 'hidden';
}
function closeDrawer() {
  document.getElementById('bookingDrawer').classList.remove('is-open');
  document.body.style.overflow = '';
}
<?php if ($openId): ?>document.addEventListener('DOMContentLoaded', () => openBookingDrawer(<?= $openId ?>));<?php endif; ?>
</script>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
