<?php
$pageTitle = 'Payroll | C.D. Zaplan Trucking';
$portal = 'admin'; $active = 'payroll';
require_once __DIR__ . '/includes/portal-header.php';
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Payroll</h1>
    <p class="page-sub">Weekly compensation for heavy equipment operators and drivers.</p>
  </div>
</div>
<?php require __DIR__ . '/includes/payroll-card.php'; ?>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
