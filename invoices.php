<?php
$pageTitle = 'Invoices & Receipts | C.D. Zaplan Trucking';
$portal = 'client'; $active = 'invoices';
require_once __DIR__ . '/includes/portal-header.php';

$invoices = [
  ['no' => 'INV-2026-031', 'ref' => 'BK-1082', 'desc' => '10-Wheeler Dump Truck',  'issued' => 'Oct 28, 2026', 'due' => 'Nov 11, 2026', 'amount' => '₱45,000.00', 'status' => 'Pending'],
  ['no' => 'INV-2026-029', 'ref' => 'BK-1045', 'desc' => 'Backhoe Loader',         'issued' => 'Oct 12, 2026', 'due' => 'Oct 26, 2026', 'amount' => '₱88,000.00', 'status' => 'Paid'],
  ['no' => 'INV-2026-024', 'ref' => 'BK-1031', 'desc' => 'Backhoe – Site Clearing', 'issued' => 'Sep 18, 2026', 'due' => 'Oct 02, 2026', 'amount' => '₱32,000.00', 'status' => 'Overdue'],
  ['no' => 'INV-2026-017', 'ref' => 'BK-1009', 'desc' => '10-Wheeler Dump Truck',  'issued' => 'Aug 04, 2026', 'due' => 'Aug 18, 2026', 'amount' => '₱64,500.00', 'status' => 'Paid'],
];
$stats = [
  ['Outstanding Balance', '₱77,000',  '2 invoices unpaid',      'lucide:receipt'],
  ['Paid This Year',      '₱152,500', '2 receipts available',   'lucide:circle-check'],
  ['Next Due Date',       'Nov 11',   'INV-2026-031 · ₱45,000', 'lucide:calendar-clock'],
];
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Invoices &amp; Receipts</h1>
    <p class="page-sub">View your billing history and download receipts.</p>
  </div>
  <button class="btn btn--ghost">
    <iconify-icon icon="lucide:calendar" width="14"></iconify-icon> 2026 <iconify-icon icon="lucide:chevron-down" width="14"></iconify-icon>
  </button>
</div>

<div class="grid-stats grid-stats--3">
  <?php foreach ($stats as [$l,$v,$s,$i]): ?>
  <div class="stat-card">
    <div class="stat-card__top"><span><?= $l ?></span><iconify-icon icon="<?= $i ?>" width="16"></iconify-icon></div>
    <div class="stat-card__value" <?= preg_match('/\d/', $v) && $v !== 'Nov 11' ? 'data-count' : '' ?>><?= $v ?></div>
    <div class="stat-card__sub"><?= $s ?></div>
  </div>
  <?php endforeach; ?>
</div>

<section class="card">
  <div class="card__head">
    <div>
      <h2 class="card__title">Billing History</h2>
      <p class="card__sub"><?= count($invoices) ?> invoices · Payment by cash on delivery / on site</p>
    </div>
  </div>
  <div class="table-scroll">
    <table class="data-table" style="--min:820px">
      <thead>
        <tr><th>Invoice</th><th>Booking</th><th>Issued</th><th>Due</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $inv): ?>
        <tr>
          <td><div class="person__name"><?= e($inv['no']) ?></div><div class="person__sub"><?= e($inv['desc']) ?></div></td>
          <td><?= e($inv['ref']) ?></td>
          <td class="cell-muted"><?= e($inv['issued']) ?></td>
          <td class="cell-muted"><?= e($inv['due']) ?></td>
          <td class="cell-strong"><?= e($inv['amount']) ?></td>
          <td><?= pill($inv['status']) ?></td>
          <td><div class="row-actions">
            <a href="#" class="link-edit">View</a>
            <a href="#" class="link-icon" aria-label="Download PDF"><iconify-icon icon="lucide:download" width="14"></iconify-icon> <?= $inv['status'] === 'Paid' ? 'Receipt' : 'Invoice' ?></a>
          </div></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card__foot">
    <span>Cash on delivery / on site only · Questions? Call 09176757888</span>
    <strong>Outstanding: ₱77,000.00</strong>
  </div>
</section>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
