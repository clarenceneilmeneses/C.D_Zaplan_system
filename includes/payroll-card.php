<?php
/*
 * Expects $payrollRows (payroll_entries joined with employees) and $payrollPeriod ([start, end] or null).
 * Optional: $payrollActions (true on payroll.php to show "Mark Paid" / "Delete").
 */
$payrollActions = $payrollActions ?? false;
$payTotal = array_sum(array_column($payrollRows, 'total'));
$payHours = array_sum(array_column($payrollRows, 'hours'));
$exportUrl = 'payroll.php?export=csv' . ($payrollPeriod ? '&period=' . urlencode($payrollPeriod[0]) : '');
$hrs = fn($h) => rtrim(rtrim(number_format((float)$h, 2), '0'), '.');
?>
<section class="card">
  <div class="card__head">
    <div>
      <h2 class="card__title">Payroll Summary</h2>
      <p class="card__sub">
        <?= $payrollPeriod ? fdate($payrollPeriod[0], 'M d') . '–' . fdate($payrollPeriod[1], 'M d, Y') . ' · Weekly payroll' : 'No payroll entries yet' ?>
      </p>
    </div>
    <a href="<?= e($exportUrl) ?>" class="btn btn--outline btn--shrink">
      <iconify-icon icon="lucide:download" width="16"></iconify-icon> Export Payroll
    </a>
  </div>
  <div class="table-wrap">
    <table class="table table--min-640">
      <thead>
        <tr>
          <th>Employee Name</th><th>Position</th><th>Hours Worked</th><th>Rate</th>
          <th>Total Pay</th><th>Payment Status</th><?php if ($payrollActions): ?><th>Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payrollRows as $p): ?>
        <tr>
          <td><div class="person">
            <span class="avatar"><?= e(initials($p['full_name'])) ?></span>
            <span class="cell-title"><?= e($p['full_name']) ?></span></div></td>
          <td><?= e($p['position']) ?></td>
          <td><?= $hrs($p['hours']) ?> hrs</td>
          <td class="td-muted"><?= money($p['rate']) ?>/hr</td>
          <td><?= money($p['total']) ?></td>
          <td><?= pill($p['status']) ?></td>
          <?php if ($payrollActions): ?>
          <td>
            <form method="post" class="row-actions">
              <?= csrf_field() ?><input type="hidden" name="entry_id" value="<?= (int)$p['id'] ?>">
              <?php if ($p['status'] === 'Pending'): ?>
              <button name="action" value="mark_paid" class="btn btn--amber btn--sm">Mark Paid</button>
              <?php endif; ?>
              <button name="action" value="delete_entry" class="btn btn--ghost btn--sm" onclick="return confirm('Delete this payroll entry?')">Delete</button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (!$payrollRows): ?>
        <tr><td colspan="7" class="empty">No payroll entries for this period.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card__foot card__foot--split">
    <span><?= count($payrollRows) ?> employees · <?= $hrs($payHours) ?> hours worked</span>
    <span class="card__total">Total payroll: <?= money($payTotal) ?></span>
  </div>
</section>
