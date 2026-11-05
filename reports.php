<?php
/* The three reports the client asked for: Monthly Rental Revenue, Equipment Availability Logs, Client Booking History */
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

// Every report follows the top-bar date range. Each table pages on its own (?rev_page=, ?log_page=, ?hist_page=).
// Revenue = confirmed + completed rentals, grouped by the month the rental starts
[$w, $p] = where(['status IN ("Confirmed", "Completed")', []], date_where('start_date'));
$revenueSql = "SELECT DATE_FORMAT(start_date, '%Y-%m') AS ym, COUNT(*) AS bookings, SUM(total_amount) AS revenue
               FROM bookings $w GROUP BY ym ORDER BY ym DESC";
$revenueParams = $p;
$revenueTotal = (float)q("SELECT COALESCE(SUM(total_amount), 0) FROM bookings $w", $p)->fetchColumn();

[$w, $p] = where(date_where('l.logged_at'));
$availabilitySql = "SELECT l.logged_at, e.asset_code, t.name AS type_name, l.from_status, l.to_status, l.note
                    FROM equipment_logs l JOIN equipment e ON e.id = l.equipment_id JOIN equipment_types t ON t.id = e.type_id
                    $w ORDER BY l.logged_at DESC, l.id DESC";
$availabilityParams = $p;

[$w, $p] = where(date_where('b.start_date'));
$historySql = "SELECT u.full_name, u.email, COUNT(b.id) AS bookings, MAX(b.start_date) AS latest,
                      COALESCE(SUM(CASE WHEN b.status IN ('Confirmed', 'Completed') THEN b.total_amount END), 0) AS billed
               FROM users u JOIN bookings b ON b.user_id = u.id
               $w GROUP BY u.id, u.full_name, u.email ORDER BY latest DESC";
$historyParams = $p;

// CSV exports contain every row in the date range, not just the page on screen
$export = $_GET['export'] ?? '';
if ($export) {
  $revenue      = $export === 'revenue'      ? q($revenueSql, $revenueParams)->fetchAll() : [];
  $availability = $export === 'availability' ? q($availabilitySql, $availabilityParams)->fetchAll() : [];
  $history      = $export === 'history'      ? q($historySql, $historyParams)->fetchAll() : [];
}
switch ($export) {
  case 'revenue':
    csv_download('monthly-revenue.csv', ['Month', 'Bookings', 'Revenue'],
      array_map(fn($r) => [fdate($r['ym'] . '-01', 'M Y'), $r['bookings'], $r['revenue']], $revenue));
  case 'availability':
    csv_download('equipment-availability.csv', ['Date', 'Unit', 'Equipment', 'From', 'To', 'Note'],
      array_map(fn($r) => [$r['logged_at'], $r['asset_code'], $r['type_name'], $r['from_status'], $r['to_status'], $r['note']], $availability));
  case 'history':
    csv_download('client-booking-history.csv', ['Client', 'Email', 'Total Bookings', 'Latest Booking', 'Total Billed'],
      array_map(fn($r) => [$r['full_name'], $r['email'], $r['bookings'], $r['latest'], $r['billed']], $history));
}

$revPager  = paginate($revenueSql, $revenueParams, 12, 'rev_page');
$logPager  = paginate($availabilitySql, $availabilityParams, 10, 'log_page');
$histPager = paginate($historySql, $historyParams, 10, 'hist_page');
$revenue = $revPager['rows']; $availability = $logPager['rows']; $history = $histPager['rows'];
$rangeLabel = date_filter()['label'];

$pageTitle = 'Reports | C.D. Zaplan Trucking';
$portal = 'admin'; $active = 'reports';
$dateFilter = true;
require_once __DIR__ . '/includes/portal-header.php';
?>
<h1 class="page-title">Reports</h1>
<p class="page-sub page-sub--spaced">Revenue, equipment availability, and client booking activity.</p>

<div class="stack">

  <section class="card" id="revenue">
    <div class="card__head">
      <div><h2 class="card__title">Monthly Rental Revenue</h2><p class="card__sub">Completed and confirmed rentals per month · <?= e($rangeLabel) ?> · Total <?= money($revenueTotal) ?></p></div>
      <a href="reports.php?export=revenue" class="btn btn--outline btn--shrink"><iconify-icon icon="lucide:download" width="16"></iconify-icon> Export</a>
    </div>
    <div class="table-wrap"><table class="table table--min-480">
      <thead><tr><th>Month</th><th>Bookings</th><th>Revenue</th></tr></thead>
      <tbody><?php foreach ($revenue as $r): ?>
        <tr><td class="td-strong"><?= fdate($r['ym'] . '-01', 'M Y') ?></td><td><?= (int)$r['bookings'] ?></td><td class="td-strong"><?= money($r['revenue']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$revenue): ?><tr><td colspan="3" class="empty">No confirmed rentals in this period.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
    <?= pagination($revPager, 'months', 'revenue') ?>
  </section>

  <section class="card" id="availability">
    <div class="card__head">
      <div><h2 class="card__title">Equipment Availability Logs</h2><p class="card__sub">Status changes for each truck and backhoe · <?= e($rangeLabel) ?></p></div>
      <a href="reports.php?export=availability" class="btn btn--outline btn--shrink"><iconify-icon icon="lucide:download" width="16"></iconify-icon> Export</a>
    </div>
    <div class="table-wrap"><table class="table table--min-760">
      <thead><tr><th>Date</th><th>Unit</th><th>Equipment</th><th>From</th><th>To</th><th>Note</th></tr></thead>
      <tbody><?php foreach ($availability as $r): ?>
        <tr><td class="td-muted"><?= fdate($r['logged_at']) ?></td><td class="td-strong"><?= e($r['asset_code']) ?></td><td><?= e($r['type_name']) ?></td>
            <td><?= pill($r['from_status']) ?></td><td><?= pill($r['to_status']) ?></td><td class="td-muted"><?= e($r['note']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$availability): ?><tr><td colspan="6" class="empty">No status changes logged in this period.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
    <?= pagination($logPager, 'log entries', 'availability') ?>
  </section>

  <section class="card" id="history">
    <div class="card__head">
      <div><h2 class="card__title">Client Booking History</h2><p class="card__sub">Bookings and billing per client · rentals starting <?= e(mb_strtolower($rangeLabel) === 'all time' ? 'any time' : $rangeLabel) ?></p></div>
      <a href="reports.php?export=history" class="btn btn--outline btn--shrink"><iconify-icon icon="lucide:download" width="16"></iconify-icon> Export</a>
    </div>
    <div class="table-wrap"><table class="table table--min-560">
      <thead><tr><th>Client</th><th>Total Bookings</th><th>Latest Booking</th><th>Total Billed</th></tr></thead>
      <tbody><?php foreach ($history as $r): ?>
        <tr><td><div class="person"><span class="avatar"><?= e(initials($r['full_name'])) ?></span><span class="cell-title"><?= e($r['full_name']) ?></span></div></td>
            <td><?= (int)$r['bookings'] ?></td><td class="td-muted"><?= fdate($r['latest']) ?></td><td class="td-strong"><?= money($r['billed']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$history): ?><tr><td colspan="4" class="empty">No bookings in this period.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
    <?= pagination($histPager, 'clients', 'history') ?>
  </section>
</div>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
