<?php
/*
 * Shared shell for admin + client pages.
 * Set before including:  $portal = 'admin' | 'client';  $active = 'overview' | 'fleet' | ...;  $pageTitle
 */
$hidePublicHeader = true;
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/mock-data.php';

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
  $who = ['Carlito Zaplan', 'Administrator'];
  $searchHint = 'Search users, bookings, or fleet...';
} else {
  $brandSub = 'CLIENT PORTAL';
  $menuLabel = 'CLIENT MENU';
  $menu = [
    'bookings' => ['My Bookings',          'dashboard.php',                        'lucide:truck'],
    'fleet'    => ['Fleet & Availability', 'availability.php',                     'lucide:layout-grid'],
    'estimator'=> ['Fee Estimator',        'estimator.php',                        'lucide:calculator'],
    'new'      => ['New Rental Request',   "javascript:openModal('bookingModal')", 'lucide:plus'],
    'invoices' => ['Invoices & Receipts',  'invoices.php',                         'lucide:file-text'],
    'settings' => ['Account Settings',     'account-settings.php',                 'lucide:settings'],
  ];
  $card = null; // client sidebar has no info card
  $footnote = 'CLIENT PORTAL · V1.0';
  $who = ['Sarah Jenkins', 'Commercial Developer'];
  $searchHint = 'Search bookings or equipment...';
}
?>
<div class="portal">

  <!-- Sidebar (tablet and up) -->
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
      <?php foreach ($menu as $key => [$label, $href, $icon]): ?>
      <a href="<?= $href ?>" class="nav-item<?= $key === $active ? ' is-active' : '' ?>">
        <iconify-icon icon="<?= $icon ?>" width="18"></iconify-icon> <?= $label ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <?php if ($card): ?>
    <div class="sidebar__card">
      <iconify-icon icon="lucide:shield-check" width="18"></iconify-icon>
      <strong><?= $card[0] ?></strong>
      <p><?= $card[1] ?></p>
    </div>
    <?php endif; ?>

    <div class="sidebar__foot"><?= $footnote ?></div>
  </aside>

  <div class="portal__main">

    <!-- Top bar -->
    <div class="topbar">
      <div class="topbar__search">
        <iconify-icon icon="lucide:search" width="16" class="search-icon"></iconify-icon>
        <input type="text" placeholder="<?= $searchHint ?>" class="search-input">
      </div>
      <div class="topbar__right">
        <button class="bell" aria-label="Notifications">
          <iconify-icon icon="lucide:bell" width="20"></iconify-icon>
          <span class="bell__dot"></span>
        </button>
        <span class="topbar__divider"></span>
        <div class="user">
          <span class="user__avatar"><?= initials($who[0]) ?></span>
          <div class="user__meta">
            <div class="user__name"><?= $who[0] ?></div>
            <div class="user__role"><?= $who[1] ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Phone nav tabs -->
    <div class="mobile-tabs">
      <?php foreach ($menu as $key => [$label, $href, $icon]): ?>
      <a href="<?= $href ?>" class="tab<?= $key === $active ? ' is-active' : '' ?>">
        <iconify-icon icon="<?= $icon ?>" width="14"></iconify-icon> <?= $label ?>
      </a>
      <?php endforeach; ?>
    </div>

    <main class="content">
