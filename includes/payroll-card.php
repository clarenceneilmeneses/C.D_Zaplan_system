<?php /* Expects $payrollSummary */ ?>
<section class="card">
  <div class="card__head">
    <div>
      <h2 class="card__title">Payroll Summary</h2>
      <p class="card__sub">Oct 14–20, 2026 · Weekly payroll</p>
    </div>
    <button onclick="handleExportPayroll()" class="btn btn--outline btn--md">
      <iconify-icon icon="lucide:download" width="16"></iconify-icon> Export Payroll
    </button>
  </div>
  <div class="table-scroll">
    <table class="data-table" style="--min:640px">
      <thead>
        <tr><th>Employee Name</th><th>Position</th><th>Hours Worked</th><th>Total Pay</th><th>Payment Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($payrollSummary as $p): ?>
        <tr>
          <td><div class="person"><span class="person__avatar"><?= initials($p['name']) ?></span><span class="person__name"><?= e($p['name']) ?></span></div></td>
          <td><?= e($p['position']) ?></td>
          <td><?= e($p['hours']) ?> hrs</td>
          <td><?= e($p['total']) ?></td>
          <td><?= pill($p['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card__foot">
    <span>3 employees · 118 hours worked</span>
    <strong>Total payroll: ₱31,000.00</strong>
  </div>
</section>
