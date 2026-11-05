<?php
/* All notifications for the logged-in user (admin or client). ?go=ID marks one read and opens what it points to. */
require_once __DIR__ . '/includes/bootstrap.php';
$me = current_user() ?: redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI']));

if (isset($_GET['go'])) {
  $n = q('SELECT * FROM notifications WHERE id = ? AND user_id = ?', [(int)$_GET['go'], $me['id']])->fetch();
  if ($n) {
    q('UPDATE notifications SET read_at = COALESCE(read_at, NOW()) WHERE id = ?', [$n['id']]);
    redirect(safe_next($n['link'], 'notifications.php'));
  }
  redirect('notifications.php');
}

if (is_post()) {
  csrf_check();
  q('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL', [$me['id']]);
  flash('success', 'All notifications marked as read.');
  redirect('notifications.php');
}

$show = ($_GET['show'] ?? '') === 'unread' ? 'unread' : 'all';
[$w, $p] = where(['user_id = ?', [$me['id']]], $show === 'unread' ? ['read_at IS NULL', []] : ['', []], date_where('created_at'));
$list = paginate("SELECT * FROM notifications $w ORDER BY created_at DESC, id DESC", $p, 15);
$unread = unread_count((int)$me['id']);

$pageTitle = 'Notifications | C.D. Zaplan Trucking';
$portal = $me['role'] === 'admin' ? 'admin' : 'client';
$active = 'notifications';
$dateFilter = true;
require_once __DIR__ . '/includes/portal-header.php';
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Notifications</h1>
    <p class="page-sub"><?= $unread ? "$unread unread" : 'You\'re all caught up.' ?></p>
  </div>
  <?php if ($unread): ?>
  <form method="post"><?= csrf_field() ?><button class="btn btn--outline"><iconify-icon icon="lucide:check-check" width="16"></iconify-icon> Mark all as read</button></form>
  <?php endif; ?>
</div>

<section class="card card--spaced">
  <div class="toolbar">
    <div class="chip-row">
      <a href="<?= e(url_with(['show' => null, 'page' => null])) ?>" class="chip<?= $show === 'all' ? ' is-active' : '' ?>">All</a>
      <a href="<?= e(url_with(['show' => 'unread', 'page' => null])) ?>" class="chip<?= $show === 'unread' ? ' is-active' : '' ?>">Unread <span><?= $unread ?></span></a>
    </div>
  </div>
  <div class="notif-page">
    <?php foreach ($list['rows'] as $n) echo notification_item(notification_view($n)); ?>
    <?php if (!$list['rows']): ?><div class="empty">No notifications<?= $show === 'unread' ? ' left to read' : '' ?>.</div><?php endif; ?>
  </div>
  <?= pagination($list, 'notifications') ?>
</section>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
