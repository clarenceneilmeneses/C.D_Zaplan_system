<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_role('client');

$from = 'FROM invoices i JOIN bookings b ON b.id = i.booking_id JOIN equipment_types t ON t.id = b.type_id';

// Billing history: search box + global date range (issue date), 10 per page
[$w, $p] = where(['b.user_id = ?', [$me['id']]],
                 like_any(['i.invoice_no', 'CONCAT("BK-", i.booking_id)', 't.name'], search_term()),
                 date_where('i.issued_on'));
$list = paginate("SELECT i.*, t.name AS equipment $from $w ORDER BY i.issued_on DESC, i.id DESC", $p);
$invoices = $list['rows'];

// Unpaid balance is always current; "paid" follows the date range (this year when the range is "All time")
$unpaid = q("SELECT i.* $from WHERE b.user_id = ? AND i.status = 'Pending' ORDER BY i.due_on", [$me['id']])->fetchAll();
$outstanding = array_sum(array_column($unpaid, 'amount'));
$next = $unpaid[0] ?? null;
$range = date_filter();
[$w, $p] = $range['from']
  ? where(['b.user_id = ? AND i.status = "Paid"', [$me['id']]], date_where('i.paid_on'))
  : where(['b.user_id = ? AND i.status = "Paid" AND YEAR(i.paid_on) = ?', [$me['id'], date('Y')]]);
$paid = q("SELECT COALESCE(SUM(i.amount), 0) AS total, COUNT(*) AS n $from $w", $p)->fetch();

$stats = [
  ['Outstanding Balance', money_short($outstanding), count($unpaid) . ' invoice' . (count($unpaid) === 1 ? '' : 's') . ' unpaid', 'lucide:receipt'],
  [$range['from'] ? 'Paid · ' . $range['label'] : 'Paid This Year', money_short($paid['total']), $paid['n'] . ' receipt' . ($paid['n'] == 1 ? '' : 's') . ' available', 'lucide:circle-check'],
  ['Next Due Date', $next ? fdate($next['due_on'], 'M d') : '—', $next ? $next['invoice_no'] . ' · ' . money($next['amount'], 0) : 'Nothing due', 'lucide:calendar-clock'],
];

$pageTitle = 'Invoices & Receipts | C.D. Zaplan Trucking';
$portal = 'client'; $active = 'invoices';
$dateFilter = true;
require_once __DIR__ . '/includes/portal-header.php';
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Invoices &amp; Receipts</h1>
    <p class="page-sub">View your billing history and download receipts.</p>
  </div>
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
  <div class="card__head card__head--block">
    <h2 class="card__title">Billing History</h2>
    <p class="card__sub"><?= $list['total'] ?> invoice<?= $list['total'] === 1 ? '' : 's' ?> · Payment by cash on delivery / on site</p>
  </div>
  <div class="toolbar toolbar--flush"><?= list_search('Search invoice no., booking ref or equipment…') ?></div>
  <div class="table-wrap">
    <table class="table table--min-820">
      <thead>
        <tr><th>Invoice</th><th>Booking</th><th>Issued</th><th>Due</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $inv): $status = invoice_status($inv); ?>
        <tr>
          <td><div class="cell-title"><?= e($inv['invoice_no']) ?></div><div class="cell-meta"><?= e($inv['equipment']) ?></div></td>
          <td><?= booking_ref($inv['booking_id']) ?></td>
          <td class="td-muted"><?= fdate($inv['issued_on']) ?></td>
          <td class="td-muted"><?= fdate($inv['due_on']) ?></td>
          <td class="td-strong"><?= money($inv['amount']) ?></td>
          <td><?= pill($status) ?></td>
          <td>
            <div class="row-actions">
              <a href="invoice.php?no=<?= urlencode($inv['invoice_no']) ?>" class="link-underline">View</a>
              <a href="invoice.php?no=<?= urlencode($inv['invoice_no']) ?>&print=1" target="_blank" class="link-icon" aria-label="Download PDF">
                <iconify-icon icon="lucide:download" width="14"></iconify-icon> <?= $status === 'Paid' ? 'Receipt' : 'Invoice' ?>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$invoices): ?>
        <tr><td colspan="7" class="empty"><?= (search_term() !== '' || $range['from'])
          ? 'No invoices match your filters.'
          : 'No invoices yet. An invoice is created when we confirm one of your bookings.' ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pagination($list, 'invoices') ?>
  <div class="card__foot card__foot--split">
    <span>Cash on delivery / on site only · Questions? Call 09176757888</span>
    <span class="card__total">Outstanding: <?= money($outstanding) ?></span>
  </div>
</section>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
