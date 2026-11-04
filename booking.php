<?php
$pageTitle = 'Rental Bookings | C.D. Zaplan Trucking';
$portal = 'admin'; $active = 'bookings';
require_once __DIR__ . '/includes/portal-header.php';
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Rental Bookings</h1>
    <p class="page-sub">Review requests, confirm deposits, and assign dispatch dates.</p>
  </div>
</div>

<section class="card">
  <div class="table-scroll">
    <table class="data-table" style="--min:980px">
      <thead>
        <tr><th>Ref ID</th><th>Client</th><th>Equipment</th><th>Project Site</th><th>Rental Start / End</th><th>Est. Amount</th><th>Deposit</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rentalBookings as $b): ?>
        <tr>
          <td class="cell-strong"><?= e($b['id']) ?></td>
          <td><div class="person">
            <span class="person__avatar"><?= initials($b['client']) ?></span>
            <div><div class="person__name"><?= e($b['client']) ?></div><div class="person__sub"><?= e($b['contact']) ?> · Valid ID on file</div></div>
          </div></td>
          <td><?= e($b['equipment']) ?></td>
          <td class="cell-muted"><?= e($b['site']) ?></td>
          <td class="cell-muted"><?= e($b['dates']) ?></td>
          <td class="cell-strong"><?= e($b['amount']) ?></td>
          <td><?= pill($b['deposit']) ?></td>
          <td><?= pill($b['status']) ?></td>
          <td><div class="row-actions">
            <?php if ($b['status'] === 'Pending Review'): ?><button class="btn btn--amber btn--sm">Approve</button><?php endif; ?>
            <a href="#" class="link-edit">View</a>
          </div></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card__foot">
    <span>A down payment or security deposit is required before a booking is confirmed. Delivery / mobilization fees are charged per distance and trip. Payment is cash on delivery / on site.</span>
  </div>
</section>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
