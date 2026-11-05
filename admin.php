<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$today = date('Y-m-d');
$available = (int)q('SELECT COUNT(*) FROM equipment WHERE status = "Available"')->fetchColumn();
$fleetSize = (int)q('SELECT COUNT(*) FROM equipment')->fetchColumn();
$activeJobs = (int)q('SELECT COUNT(*) FROM bookings WHERE status = "Confirmed" AND start_date <= ? AND end_date >= ?', [$today, $today])->fetchColumn();
$pendingJobs = (int)q('SELECT COUNT(*) FROM bookings WHERE status = "Pending Review"')->fetchColumn();
$userCount = (int)q('SELECT COUNT(*) FROM users')->fetchColumn();
$adminCount = (int)q('SELECT COUNT(*) FROM users WHERE role = "admin"')->fetchColumn();
// Revenue = confirmed + completed rentals by start date (same rule as the Reports page).
// Follows the top-bar date range; with "All time" it shows the current month.
$range = date_filter();
[$w, $p] = $range['from']
  ? where(['status IN ("Confirmed", "Completed")', []], date_where('start_date'))
  : where(['status IN ("Confirmed", "Completed") AND start_date >= ? AND start_date <= ?', [date('Y-m-01'), date('Y-m-t')]]);
$revenue = (float)q("SELECT COALESCE(SUM(total_amount), 0) FROM bookings $w", $p)->fetchColumn();

$stats = [
  ['Available Units',  $available,              "Ready for deployment · $fleetSize in fleet", 'lucide:truck'],
  ['Active Projects',  $activeJobs,             "On site today · $pendingJobs awaiting review", 'lucide:hard-hat'],
  ['Total Users',      $userCount,              ($userCount - $adminCount) . " clients · $adminCount admin" . ($adminCount === 1 ? '' : 's'), 'lucide:users'],
  [$range['from'] ? 'Revenue' : 'Monthly Revenue', money_short($revenue), $range['from'] ? $range['label'] : date('F Y'), 'lucide:banknote'],
];

// Cards below reuse the same partials as the Users and Payroll pages (newest users / payroll week inside the date range)
[$w, $p] = where(date_where('created_at'));
$registeredUsers = q("SELECT * FROM users $w ORDER BY created_at DESC LIMIT 4", $p)->fetchAll();
[$w, $p] = where(date_overlap('period_start', 'period_end'));
$payrollPeriod = q("SELECT period_start, period_end FROM payroll_entries $w ORDER BY period_start DESC LIMIT 1", $p)->fetch(PDO::FETCH_NUM) ?: null;
$payrollRows = $payrollPeriod
  ? q('SELECT p.*, e.full_name, e.position FROM payroll_entries p JOIN employees e ON e.id = p.employee_id
       WHERE p.period_start = ? ORDER BY e.full_name', [$payrollPeriod[0]])->fetchAll()
  : [];

$pageTitle = 'Admin Dashboard | C.D. Zaplan Trucking';
$portal = 'admin'; $active = 'overview';
$dateFilter = true;
require_once __DIR__ . '/includes/portal-header.php';
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Operations overview</h1>
    <p class="page-sub">A clear view of your fleet, people, and payroll · <?= date('l, F j, Y') ?></p>
  </div>
</div>

<div class="stat-grid stat-grid--4">
  <?php foreach ($stats as [$label, $value, $sub, $icon]): ?>
  <div class="stat-card">
    <div class="stat-card__head">
      <span><?= $label ?></span><iconify-icon icon="<?= $icon ?>" width="16" class="stat-card__icon"></iconify-icon>
    </div>
    <div class="stat-card__value"><?= e($value) ?></div>
    <div class="stat-card__sub"><?= e($sub) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="stack stack--spaced">
  <?php require __DIR__ . '/includes/users-card.php'; ?>
  <?php require __DIR__ . '/includes/payroll-card.php'; ?>
</div>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
