<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

if (is_post()) {
  csrf_check();
  $action = $_POST['action'] ?? '';

  if ($action === 'save_unit') {
    $id    = (int)($_POST['id'] ?? 0);
    $code  = strtoupper(trim($_POST['asset_code'] ?? ''));
    $plate = trim($_POST['plate_no'] ?? '');
    $type  = equipment_type((int)($_POST['type_id'] ?? 0));
    $model = trim($_POST['model'] ?? '');
    $cap   = trim($_POST['capacity'] ?? '');
    if ($code === '' || !$type || $model === '' || $cap === '') {
      flash('error', 'Asset ID, equipment type, model and capacity are required.');
    } elseif (q('SELECT 1 FROM equipment WHERE asset_code = ? AND id <> ?', [$code, $id])->fetchColumn()) {
      flash('error', "Asset ID $code is already used.");
    } elseif ($id) {
      q('UPDATE equipment SET asset_code = ?, plate_no = ?, type_id = ?, model = ?, capacity = ? WHERE id = ?',
        [$code, $plate ?: null, $type['id'], $model, $cap, $id]);
      flash('success', "$code updated.");
    } else {
      q('INSERT INTO equipment (asset_code, plate_no, type_id, model, capacity) VALUES (?, ?, ?, ?, ?)',
        [$code, $plate ?: null, $type['id'], $model, $cap]);
      flash('success', "$code added to the fleet.");
    }
  }

  if ($action === 'set_status') {
    $to = $_POST['status'] ?? '';
    if (in_array($to, ['Available', 'Deployed', 'Maintenance'], true)) {
      set_equipment_status((int)$_POST['id'], $to, trim($_POST['note'] ?? '') ?: 'Status changed by admin');
      flash('success', 'Unit status updated.');
    }
  }

  if ($action === 'delete_unit') {
    $busy = q('SELECT COUNT(*) FROM bookings WHERE equipment_id = ? AND status = "Confirmed"', [(int)$_POST['id']])->fetchColumn();
    if ($busy) flash('error', 'This unit is assigned to a confirmed booking. Complete or cancel it first.');
    else { q('DELETE FROM equipment WHERE id = ?', [(int)$_POST['id']]); flash('success', 'Unit removed.'); }
  }

  if ($action === 'save_rates') {
    foreach ($_POST['rates'] ?? [] as $typeId => $r) {
      $vals = [$r['daily_rate'] ?? '', $r['per_km_rate'] ?? '', $r['min_trip_fee'] ?? ''];
      if (count(array_filter($vals, fn($v) => is_numeric($v) && $v >= 0)) !== 3) { flash('error', 'Rates must be numbers of 0 or more.'); redirect('fleet.php'); }
      q('UPDATE equipment_types SET daily_rate = ?, per_km_rate = ?, min_trip_fee = ? WHERE id = ?', [...$vals, (int)$typeId]);
    }
    $lat = $_POST['base_lat'] ?? ''; $lng = $_POST['base_lng'] ?? ''; $max = $_POST['max_service_km'] ?? '';
    if (is_numeric($lat) && is_numeric($lng) && is_numeric($max) && abs($lat) <= 90 && abs($lng) <= 180 && $max > 0) {
      foreach (['base_lat' => $lat, 'base_lng' => $lng, 'max_service_km' => $max, 'base_label' => trim($_POST['base_label'] ?? '')] as $k => $v) {
        if ($v !== '') q('UPDATE settings SET `value` = ? WHERE `key` = ?', [$v, $k]);
      }
      flash('success', 'Pricing and yard location saved. The website estimator uses the new values right away.');
    } else {
      flash('error', 'Yard latitude/longitude and service radius must be valid numbers.');
    }
  }
  redirect(url_with());
}

// Equipment table: search box + status filter, 10 per page (the fleet is current state, so no date range here)
$unitStatus = in_array($_GET['status'] ?? '', ['Available', 'Deployed', 'Maintenance'], true) ? $_GET['status'] : '';
[$w, $p] = where(like_any(['e.asset_code', 'e.plate_no', 'e.model', 'e.capacity', 't.name'], search_term()),
                 $unitStatus ? ['e.status = ?', [$unitStatus]] : ['', []]);
$unitPager = paginate("SELECT e.*, t.name AS type_name,
              (SELECT CONCAT('BK-', b.id) FROM bookings b WHERE b.equipment_id = e.id AND b.status = 'Confirmed' ORDER BY b.start_date LIMIT 1) AS booking
            FROM equipment e JOIN equipment_types t ON t.id = e.type_id $w ORDER BY e.asset_code", $p);
$units = $unitPager['rows'];
$types = equipment_types();
$byType = q('SELECT t.name, COUNT(e.id) FROM equipment_types t LEFT JOIN equipment e ON e.type_id = t.id GROUP BY t.id, t.name')->fetchAll(PDO::FETCH_KEY_PAIR);
$fleetTotal = (int)q('SELECT COUNT(*) FROM equipment')->fetchColumn();
$fleetFree = (int)q('SELECT COUNT(*) FROM equipment WHERE status = "Available"')->fetchColumn();
$stats = [['Total Fleet Units', $fleetTotal, "$fleetFree available right now"]];
foreach ($types as $t) $stats[] = [$t['name'] . 's', $byType[$t['name']] ?? 0, $t['description'] ?: ''];

$pageTitle = 'Fleet Management | C.D. Zaplan Trucking';
$portal = 'admin'; $active = 'fleet';
require_once __DIR__ . '/includes/portal-header.php';
?>
<h1 class="page-title">Fleet Management</h1>
<p class="page-sub">Your trucks and backhoes, with the details you track for each unit.</p>

<div class="stat-grid">
  <?php foreach (array_slice($stats, 0, 3) as [$l,$v,$sub]): ?>
  <div class="stat-card">
    <div class="stat-card__head"><span><?= e($l) ?></span></div>
    <div class="stat-card__value"><?= (int)$v ?></div>
    <div class="stat-card__sub stat-card__sub--tight"><?= e($sub) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<section class="card card--spaced">
  <div class="card__head">
    <h2 class="card__title">Equipment</h2>
    <button onclick="openUnitForm()" class="btn btn--amber">
      <iconify-icon icon="lucide:plus" width="16"></iconify-icon> Add Equipment
    </button>
  </div>
  <div class="toolbar toolbar--flush">
    <?= list_search('Search asset ID, plate, model…') ?>
    <select onchange="location.href = this.value" class="select" aria-label="Unit status">
      <?php foreach (['' => 'Status: All', 'Available' => 'Standby (Available)', 'Deployed' => 'Deployed', 'Maintenance' => 'Maintenance'] as $val => $label): ?>
      <option value="<?= e(url_with(['status' => $val ?: null, 'page' => null])) ?>" <?= $unitStatus === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="table-wrap">
    <table class="table table--min-860">
      <thead>
        <tr><th>Asset ID</th><th>Plate Number</th><th>Equipment</th><th>Model &amp; Brand</th><th>Load Capacity / Tonnage</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($units as $u): ?>
        <tr>
          <td class="td-strong"><?= e($u['asset_code']) ?></td>
          <td><?= e($u['plate_no'] ?: 'Asset ID only') ?></td>
          <td><?= e($u['type_name']) ?></td>
          <td><?= e($u['model']) ?></td>
          <td class="td-muted"><?= e($u['capacity']) ?></td>
          <td>
            <form method="post" class="status-form">
              <?= csrf_field() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <select name="status" class="select select--sm" onchange="this.form.note.value = prompt('Note for the availability log (optional):', '') ?? ''; this.form.submit()">
                <?php foreach (['Available', 'Deployed', 'Maintenance'] as $s): ?>
                <option value="<?= $s ?>" <?= $u['status'] === $s ? 'selected' : '' ?>><?= $s === 'Available' ? 'Standby (Available)' : $s ?></option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="note">
            </form>
            <?php if ($u['booking']): ?><div class="cell-meta">Booked: <?= e($u['booking']) ?></div><?php endif; ?>
          </td>
          <td><div class="row-actions">
            <button type="button" class="link-underline" data-unit="<?= e(json_encode($u)) ?>" onclick="openUnitForm(JSON.parse(this.dataset.unit))">Edit</button>
            <form method="post" onsubmit="return confirm('Remove <?= e($u['asset_code']) ?> from the fleet?')">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button name="action" value="delete_unit" class="btn btn--ghost btn--sm">Remove</button>
            </form>
          </div></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$units): ?><tr><td colspan="7" class="empty"><?= $fleetTotal ? 'No units match your filters.' : 'No equipment yet. Click Add Equipment.' ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pagination($unitPager, 'units') ?>
</section>

<!-- Pricing used by the website estimator and new bookings -->
<section class="card card--spaced">
  <div class="card__head card__head--block">
    <h2 class="card__title">Rates &amp; Estimator Settings</h2>
    <p class="card__sub">Estimate = daily rate × days + 2 trips × the higher of (km × per-km rate) or the minimum trip fee. Changes apply to new estimates and bookings only.</p>
  </div>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="save_rates">
    <div class="table-wrap">
      <table class="table table--min-640">
        <thead><tr><th>Equipment</th><th>Daily rate (₱)</th><th>Per km, one way (₱)</th><th>Minimum trip fee (₱)</th></tr></thead>
        <tbody>
          <?php foreach ($types as $t): ?>
          <tr>
            <td class="td-strong"><?= e($t['name']) ?></td>
            <td><input name="rates[<?= (int)$t['id'] ?>][daily_rate]"   type="number" step="0.01" min="0" value="<?= e($t['daily_rate']) ?>"   class="field field--sm" required></td>
            <td><input name="rates[<?= (int)$t['id'] ?>][per_km_rate]"  type="number" step="0.01" min="0" value="<?= e($t['per_km_rate']) ?>"  class="field field--sm" required></td>
            <td><input name="rates[<?= (int)$t['id'] ?>][min_trip_fee]" type="number" step="0.01" min="0" value="<?= e($t['min_trip_fee']) ?>" class="field field--sm" required></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card__body form-grid form-grid--two form-grid--four">
      <div><label class="label">Yard name</label><input name="base_label" type="text" maxlength="120" value="<?= e(setting('base_label')) ?>" class="field field--sm"></div>
      <div><label class="label">Yard latitude</label><input name="base_lat" type="number" step="0.000001" value="<?= e(setting('base_lat')) ?>" class="field field--sm" required></div>
      <div><label class="label">Yard longitude</label><input name="base_lng" type="number" step="0.000001" value="<?= e(setting('base_lng')) ?>" class="field field--sm" required></div>
      <div><label class="label">Service radius (km)</label><input name="max_service_km" type="number" step="1" min="1" value="<?= e(setting('max_service_km')) ?>" class="field field--sm" required></div>
    </div>
    <div class="card__foot card__foot--split">
      <span>Tip: find the yard's coordinates by right-clicking it in Google Maps.</span>
      <button class="btn btn--amber">Save rates</button>
    </div>
  </form>
</section>

<!-- Add / edit unit -->
<div id="unitModal" class="modal hidden" onclick="if (event.target === this) closeModal('unitModal')">
  <div class="dialog dialog--padded">
    <div class="dialog__head">
      <h2 class="dialog__title" id="unitTitle">Add Equipment</h2>
      <button onclick="closeModal('unitModal')" aria-label="Close" class="close-btn close-btn--plain"><iconify-icon icon="lucide:x" width="20"></iconify-icon></button>
    </div>
    <form method="post" id="unitForm" class="form form--modal">
      <?= csrf_field() ?><input type="hidden" name="action" value="save_unit"><input type="hidden" name="id">
      <div class="form-grid form-grid--two">
        <div><label class="label">Asset ID</label><input name="asset_code" required maxlength="20" placeholder="TRK-04" class="field"></div>
        <div><label class="label">Plate Number</label><input name="plate_no" maxlength="30" placeholder="Leave blank for backhoes" class="field"></div>
      </div>
      <div><label class="label">Equipment</label>
        <select name="type_id" class="field"><?php foreach ($types as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
      <div><label class="label">Model &amp; Brand</label><input name="model" required maxlength="100" placeholder="Isuzu Giga" class="field"></div>
      <div><label class="label">Load Capacity / Tonnage</label><input name="capacity" required maxlength="60" placeholder="20 Tons / 15 cu.m" class="field"></div>
      <button class="btn btn--amber btn--block">Save</button>
    </form>
  </div>
</div>
<script>
function openUnitForm(u) {
  const f = document.getElementById('unitForm');
  f.reset();
  document.getElementById('unitTitle').textContent = u ? 'Edit ' + u.asset_code : 'Add Equipment';
  f.elements['id'].value = u ? u.id : '';
  if (u) ['asset_code', 'plate_no', 'type_id', 'model', 'capacity'].forEach(k => { f.elements[k].value = u[k] ?? ''; });
  openModal('unitModal');
}
</script>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
