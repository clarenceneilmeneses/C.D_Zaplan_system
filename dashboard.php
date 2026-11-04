<?php
/* NOTE: the Figma file has no client-dashboard frame. This page reuses the admin design
   system (same sidebar, cards, tables) so it matches until a design exists. */
$pageTitle = 'Client Dashboard | C.D. Zaplan Trucking';
$portal = 'client'; $active = 'bookings';
require_once __DIR__ . '/includes/portal-header.php';

$userBookings = [
  ['id' => 'BK-1082', 'equipment' => '10-Wheeler Dump Truck', 'location' => 'Dagupan Site A',              'dates' => 'Nov 02 - Nov 10, 2026', 'status' => 'Confirmed',      'cost' => '₱45,000.00'],
  ['id' => 'BK-1045', 'equipment' => 'Backhoe Loader',        'location' => 'San Fabian Commercial Build', 'dates' => 'Oct 12 - Oct 28, 2026', 'status' => 'Completed',      'cost' => '₱88,000.00'],
  ['id' => 'BK-1099', 'equipment' => 'Backhoe',               'location' => 'Urdaneta Bypass Site',        'dates' => 'Nov 15 - Nov 20, 2026', 'status' => 'Pending Review', 'cost' => '₱32,000.00'],
];
$stats = [
  ['Active Projects',   '1',     'Dagupan Site A · On schedule', 'lucide:truck'],
  ['Pending Inquiries', '1',     'Awaiting admin review',        'lucide:clock'],
  ['Total Spent',       '₱165K', 'Across 3 bookings',            'lucide:banknote'],
];
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Welcome back, Sarah!</h1>
    <p class="page-sub">Track your active equipment deployments and manage bookings.</p>
  </div>
  <div class="row-actions">
    <a href="estimator.php" class="btn btn--outline btn--md"><iconify-icon icon="lucide:calculator" width="16"></iconify-icon> Estimate Cost</a>
    <button onclick="openModal('bookingModal')" class="btn btn--amber btn--md"><iconify-icon icon="lucide:plus" width="16"></iconify-icon> Book Equipment</button>
  </div>
</div>

<div class="grid-stats grid-stats--3">
  <?php foreach ($stats as [$l,$v,$s,$i]): ?>
  <div class="stat-card">
    <div class="stat-card__top"><span><?= $l ?></span><iconify-icon icon="<?= $i ?>" width="16"></iconify-icon></div>
    <div class="stat-card__value" data-count><?= $v ?></div>
    <div class="stat-card__sub"><?= $s ?></div>
  </div>
  <?php endforeach; ?>
</div>

<section class="card">
  <div class="card__head"><h2 class="card__title">My Bookings</h2></div>
  <div class="table-scroll">
    <table class="data-table" style="--min:720px">
      <thead><tr><th>Ref ID</th><th>Equipment</th><th>Location</th><th>Schedule</th><th>Status</th><th>Cost</th></tr></thead>
      <tbody>
        <?php foreach ($userBookings as $b): ?>
        <tr>
          <td class="cell-strong"><?= e($b['id']) ?></td>
          <td><?= e($b['equipment']) ?></td>
          <td class="cell-muted"><?= e($b['location']) ?></td>
          <td class="cell-muted"><?= e($b['dates']) ?></td>
          <td><?= pill($b['status']) ?></td>
          <td class="cell-strong"><?= e($b['cost']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
