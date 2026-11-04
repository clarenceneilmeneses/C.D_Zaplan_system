<?php
/* The three reports the client asked for: Monthly Rental Revenue, Equipment Availability Logs, Client Booking History */
$pageTitle = 'Reports | C.D. Zaplan Trucking';
$portal = 'admin'; $active = 'reports';
require_once __DIR__ . '/includes/portal-header.php';

$revenue = [
  ['Oct 2026', 18, '₱1,250,000.00'], ['Sep 2026', 15, '₱1,080,000.00'],
  ['Aug 2026', 13, '₱960,000.00'],   ['Jul 2026', 12, '₱890,000.00'],
];
$availability = [
  ['Oct 28, 2026', 'TRK-02', '10-Wheeler Dump Truck', 'Deployed',    'Available',   'Returned from Dagupan Site A'],
  ['Oct 26, 2026', 'BKH-01', 'Backhoe',               'Available',   'Deployed',    'Dispatched to San Fabian'],
  ['Oct 22, 2026', 'BKH-02', 'Backhoe',               'Deployed',    'Maintenance', 'Hydraulic hose repair'],
  ['Oct 15, 2026', 'TRK-01', '10-Wheeler Dump Truck', 'Available',   'Deployed',    'Dispatched to Dagupan Site A'],
];
$history = [
  ['Sarah Jenkins',   3, 'Nov 02, 2026', '₱165,000.00'],
  ['Michael Chen',    1, 'Nov 05, 2026', '₱62,000.00'],
  ['Elena Rodriguez', 1, 'Oct 15, 2026', '₱38,000.00'],
];
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Reports</h1>
    <p class="page-sub">Revenue, equipment availability, and client booking activity.</p>
  </div>
</div>

<div class="stack">

  <section class="card">
    <div class="card__head">
      <div><h2 class="card__title">Monthly Rental Revenue</h2><p class="card__sub">Completed and confirmed rentals per month</p></div>
      <button onclick="alert('Export goes here.')" class="btn btn--outline btn--md"><iconify-icon icon="lucide:download" width="16"></iconify-icon> Export</button>
    </div>
    <div class="table-scroll"><table class="data-table" style="--min:480px">
      <thead><tr><th>Month</th><th>Bookings</th><th>Revenue</th></tr></thead>
      <tbody><?php foreach ($revenue as [$m,$n,$r]): ?>
        <tr><td class="cell-strong"><?= $m ?></td><td><?= $n ?></td><td class="cell-strong"><?= $r ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </section>

  <section class="card">
    <div class="card__head">
      <div><h2 class="card__title">Equipment Availability Logs</h2><p class="card__sub">Status changes for each truck and backhoe</p></div>
      <button onclick="alert('Export goes here.')" class="btn btn--outline btn--md"><iconify-icon icon="lucide:download" width="16"></iconify-icon> Export</button>
    </div>
    <div class="table-scroll"><table class="data-table" style="--min:760px">
      <thead><tr><th>Date</th><th>Unit</th><th>Equipment</th><th>From</th><th>To</th><th>Note</th></tr></thead>
      <tbody><?php foreach ($availability as [$d,$u,$t,$from,$to,$note]): ?>
        <tr><td class="cell-muted"><?= $d ?></td><td class="cell-strong"><?= $u ?></td><td><?= $t ?></td><td><?= pill($from) ?></td><td><?= pill($to) ?></td><td class="cell-muted"><?= $note ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </section>

  <section class="card">
    <div class="card__head">
      <div><h2 class="card__title">Client Booking History</h2><p class="card__sub">Bookings and billing per client</p></div>
      <button onclick="alert('Export goes here.')" class="btn btn--outline btn--md"><iconify-icon icon="lucide:download" width="16"></iconify-icon> Export</button>
    </div>
    <div class="table-scroll"><table class="data-table" style="--min:560px">
      <thead><tr><th>Client</th><th>Total Bookings</th><th>Latest Booking</th><th>Total Billed</th></tr></thead>
      <tbody><?php foreach ($history as [$c,$n,$l,$t]): ?>
        <tr><td><div class="person"><span class="person__avatar"><?= initials($c) ?></span><span class="person__name"><?= $c ?></span></div></td><td><?= $n ?></td><td class="cell-muted"><?= $l ?></td><td class="cell-strong"><?= $t ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </section>
</div>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
