<?php
$pageTitle = 'User Management | C.D. Zaplan Trucking';
$portal = 'admin'; $active = 'users';
require_once __DIR__ . '/includes/portal-header.php';
$usersSub = '48 registered users · Manage roles and permissions';
$showJoined = true;
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Users</h1>
    <p class="page-sub">Registered client accounts and system permissions.</p>
  </div>
</div>
<?php require __DIR__ . '/includes/users-card.php'; ?>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
