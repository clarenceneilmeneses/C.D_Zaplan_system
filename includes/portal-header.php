<?php
/*
 * Shared shell for admin + client pages.
 * The page must call require_role(...) BEFORE including this file.
 * Set before including:  $portal = 'admin' | 'client';  $active = 'overview' | 'fleet' | ...;  $pageTitle
 * Optional:               $dateFilter = true  → shows the global date range picker (pages whose lists use date_filter())
 */
$hidePublicHeader = true;
require_once __DIR__ . '/header.php';
$me = current_user();
$dateFilter = $dateFilter ?? false;
invoice_reminders($me);   // "due soon" / "overdue" notifications for clients
$unread = unread_count((int)$me['id']);
$range = date_filter();

if ($portal === 'admin') {
  $brandSub = 'TRUCKING';
  $menuLabel = 'WORKSPACE';
  $menu = [
    'overview' => ['Overview',         'admin.php',           'lucide:layout-dashboard'],
    'fleet'    => ['Fleet Management', 'fleet.php',           'lucide:truck'],
    'bookings' => ['Rental Bookings',  'booking.php',         'lucide:calendar-days'],
    'inquiries'=> ['Inquiries',        'inquiries.php',       'lucide:message-square-text'],
    'users'    => ['Users',            'user-management.php', 'lucide:users'],
    'payroll'  => ['Payroll',          'payroll.php',         'lucide:wallet'],
    'reports'  => ['Reports',          'reports.php',         'lucide:bar-chart-3'],
  ];
  $card = ['Built on reliability.', 'Your fleet, people, and projects in one place.'];
  $footnote = 'ADMIN WORKSPACE · V1.0';
  $roleLabel = 'Administrator';
  $searchHint = 'Search bookings, clients, units…';
} else {
  $brandSub = 'CLIENT PORTAL';
  $menuLabel = 'CLIENT MENU';
  $menu = [
    'bookings' => ['My Bookings',          'dashboard.php',        'lucide:truck'],
    'new'      => ['New Rental Request',   'dashboard.php?book=1', 'lucide:plus'],
    'invoices' => ['Invoices & Receipts',  'invoices.php',         'lucide:file-text'],
    'settings' => ['Account Settings',     'account-settings.php', 'lucide:settings'],
  ];
  $card = null; // client sidebar has no info card
  $footnote = 'CLIENT PORTAL · V1.0';
  $roleLabel = $me['company'] ?: 'Client';
  $searchHint = 'Search bookings, invoices…';
}
?>
<div class="portal">

  <!-- Sidebar (desktop) -->
  <aside class="sidebar">
    <a href="index.php" class="sidebar__brand">
      <span class="sidebar__logo"><img src="assets/img/logo.png" alt="C.D. Zaplan Trucking logo"></span>
      <span>
        <span class="sidebar__name">C.D. Zaplan</span>
        <span class="sidebar__sub"><?= $brandSub ?></span>
      </span>
    </a>

    <div class="sidebar__label"><?= $menuLabel ?></div>
    <nav class="sidebar__nav">
      <?php foreach ($menu as $key => [$label, $href, $icon]): $on = ($key === $active); ?>
      <a href="<?= $href ?>" class="nav-link<?= $on ? ' is-active' : '' ?>">
        <iconify-icon icon="<?= $icon ?>" width="18"></iconify-icon> <?= $label ?>
      </a>
      <?php endforeach; ?>
      <a href="logout.php" class="nav-link"><iconify-icon icon="lucide:log-out" width="18"></iconify-icon> Log Out</a>
    </nav>

    <?php if ($card): ?>
    <div class="sidebar__card">
      <iconify-icon icon="lucide:shield-check" width="18" class="sidebar__card-icon"></iconify-icon>
      <div class="sidebar__card-title"><?= $card[0] ?></div>
      <p class="sidebar__card-text"><?= $card[1] ?></p>
    </div>
    <?php endif; ?>

    <div class="sidebar__foot"><?= $footnote ?></div>
  </aside>

  <div class="portal__main">

    <!-- Top bar -->
    <div class="topbar">
      <!-- Global search: live results from api/search.php, Enter opens search.php -->
      <form action="search.php" method="get" class="search search--global" role="search" id="globalSearch" autocomplete="off">
        <iconify-icon icon="lucide:search" width="16" class="search__icon"></iconify-icon>
        <input id="portalSearch" name="q" type="search" value="<?= $active === 'search' ? e(search_term()) : '' ?>" placeholder="<?= e($searchHint) ?>"
               class="search__input" aria-label="Search" aria-autocomplete="list" aria-controls="searchResults" aria-expanded="false">
        <div id="searchResults" class="popover search-results hidden" role="listbox"></div>
      </form>

      <div class="topbar__right">
        <?php if ($dateFilter): ?>
        <!-- Global date range: stored in the session, applies to every list that has dates -->
        <div class="range" id="rangePicker">
          <button type="button" class="range__btn<?= $range['from'] ? ' is-set' : '' ?>" onclick="togglePanel('rangePicker')" aria-haspopup="dialog" aria-expanded="false" title="Date range: <?= e($range['label']) ?>">
            <iconify-icon icon="lucide:calendar-range" width="16"></iconify-icon>
            <span class="range__label"><?= e($range['label']) ?></span>
            <iconify-icon icon="lucide:chevron-down" width="14" class="range__chev"></iconify-icon>
          </button>
          <div class="popover range__panel hidden" role="dialog" aria-label="Date range">
            <div class="eyebrow eyebrow--muted">Date range</div>
            <div class="range__presets">
              <?php foreach (date_presets() as $key => $label): ?>
              <a href="<?= e(url_with(['range' => $key, 'from' => null, 'to' => null])) ?>" class="range__preset<?= $range['key'] === $key ? ' is-active' : '' ?>"><?= e($label) ?></a>
              <?php endforeach; ?>
            </div>
            <form method="get" class="range__custom">
              <?= keep_params(['range', 'from', 'to', 'page']) ?><input type="hidden" name="range" value="custom">
              <div class="eyebrow eyebrow--muted">Custom</div>
              <div class="range__dates">
                <label><span>From</span><input type="date" name="from" value="<?= e($range['from'] ?? '') ?>" required class="field field--sm"></label>
                <label><span>To</span><input type="date" name="to" value="<?= e($range['to'] ?? '') ?>" required class="field field--sm"></label>
              </div>
              <button class="btn btn--amber btn--sm">Apply range</button>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <!-- Notifications -->
        <div class="notif" id="notif" data-unread="<?= $unread ?>">
          <button type="button" class="icon-btn notif__btn" onclick="toggleNotifications()" aria-haspopup="dialog" aria-expanded="false" aria-label="Notifications, <?= $unread ?> unread">
            <iconify-icon icon="lucide:bell" width="20"></iconify-icon>
            <span class="notif__badge<?= $unread ? '' : ' hidden' ?>" id="notifBadge"><?= $unread > 99 ? '99+' : $unread ?></span>
          </button>
          <div class="popover notif__panel hidden" role="dialog" aria-label="Notifications">
            <div class="notif__head">
              <strong>Notifications</strong>
              <button type="button" class="notif__readall<?= $unread ? '' : ' hidden' ?>" id="notifReadAll" onclick="markAllNotificationsRead()">Mark all as read</button>
            </div>
            <div class="notif__list" id="notifList">
              <?php $recent = recent_notifications((int)$me['id']); foreach ($recent as $n) echo notification_item($n); ?>
              <?php if (!$recent): ?><div class="notif__empty">No notifications yet.</div><?php endif; ?>
            </div>
            <a href="notifications.php" class="notif__foot">View all notifications</a>
          </div>
        </div>
        <span class="topbar__divider"></span>
        <div class="user">
          <span class="avatar avatar--navy avatar--md"><?= e(initials($me['full_name'])) ?></span>
          <div class="user__info">
            <div class="user__name"><?= e($me['full_name']) ?></div>
            <div class="user__role"><?= e($roleLabel) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile nav pills -->
    <div class="mobile-tabs">
      <?php foreach ($menu as $key => [$label, $href, $icon]): $on = ($key === $active); ?>
      <a href="<?= $href ?>" class="tab<?= $on ? ' is-active' : '' ?>">
        <iconify-icon icon="<?= $icon ?>" width="14"></iconify-icon> <?= $label ?>
      </a>
      <?php endforeach; ?>
      <a href="logout.php" class="tab"><iconify-icon icon="lucide:log-out" width="14"></iconify-icon> Log Out</a>
    </div>

    <?php if ($dateFilter && $range['from']): ?>
    <div class="range-bar">
      <iconify-icon icon="lucide:calendar-range" width="14"></iconify-icon>
      <span>Date range: <strong><?= e($range['label']) ?></strong></span>
      <a href="<?= e(url_with(['range' => 'all'])) ?>">Clear</a>
    </div>
    <?php endif; ?>

    <main class="portal__content">
      <?= render_flash() ?>
