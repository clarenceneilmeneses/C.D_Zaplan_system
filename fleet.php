<?php
$pageTitle = 'Fleet Management | C.D. Zaplan Trucking';
$portal = 'admin'; $active = 'fleet';
require_once __DIR__ . '/includes/portal-header.php';

$total   = count($fleetUnits);
$trucks  = count(array_filter($fleetUnits, fn($f) => str_contains($f['type'], 'Truck')));
$backhoe = count(array_filter($fleetUnits, fn($f) => $f['type'] === 'Backhoe'));
$stats = [['Total Fleet Units', $total, 'Trucks and backhoes'], ['10-Wheeler Dump Trucks', $trucks, 'Hauling fleet'], ['Backhoes', $backhoe, 'Excavation & site clearing']];
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Fleet Management</h1>
    <p class="page-sub">Your trucks and backhoes, with the details you track for each unit.</p>
  </div>
</div>

<div class="grid-stats grid-stats--3">
  <?php foreach ($stats as [$l,$v,$sub]): ?>
  <div class="stat-card">
    <div class="stat-card__top"><span><?= $l ?></span></div>
    <div class="stat-card__value" data-count><?= $v ?></div>
    <div class="stat-card__sub"><?= $sub ?></div>
  </div>
  <?php endforeach; ?>
</div>

<section class="card">
  <div class="card__head">
    <h2 class="card__title">Equipment</h2>
    <button onclick="alert('Add Equipment form goes here.')" class="btn btn--amber btn--md">
      <iconify-icon icon="lucide:plus" width="16"></iconify-icon> Add Equipment
    </button>
  </div>
  <div class="table-scroll">
    <table class="data-table" style="--min:700px">
      <thead>
        <tr><th>Asset ID</th><th>Plate Number</th><th>Equipment</th><th>Model &amp; Brand</th><th>Load Capacity / Tonnage</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($fleetUnits as $f): ?>
        <tr>
          <td class="cell-strong"><?= e($f['id']) ?></td>
          <td><?= e($f['plate']) ?></td>
          <td><?= e($f['type']) ?></td>
          <td><?= e($f['model']) ?></td>
          <td class="cell-muted"><?= e($f['capacity']) ?></td>
          <td><a href="#" class="link-edit">Edit</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
