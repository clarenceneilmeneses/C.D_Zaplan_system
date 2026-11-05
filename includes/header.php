<?php
/*
 * Shared <head> + public navigation.
 * Set these BEFORE including:
 *   $pageTitle        string
 *   $hidePublicHeader true on login / register / admin / dashboard
 *   $useMap           true on pages that show the Leaflet map (landing page, client dashboard)
 */
require_once __DIR__ . '/bootstrap.php';
$pageTitle        = $pageTitle        ?? 'C.D. Zaplan Trucking';
$hidePublicHeader = $hidePublicHeader ?? false;
$useMap           = $useMap           ?? false;
$me               = current_user();
$portalHome       = ($me && $me['role'] === 'admin') ? 'admin.php' : 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title><?= e($pageTitle) ?></title>

  <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
  <?php if ($useMap): ?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
          integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <?php endif; ?>
  <link rel="icon" href="assets/img/logo.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php if (!$hidePublicHeader): ?>
<header class="site-header">
  <div class="site-header__inner">
    <a href="index.php" class="brand">
      <img src="assets/img/logo.png" alt="C.D. Zaplan Trucking logo" class="brand__logo">
      <span class="brand__name">C.D. Zaplan Trucking</span>
    </a>

    <nav class="site-nav">
      <a href="index.php#about">Services</a>
      <a href="index.php#estimate">Estimate</a>
      <a href="index.php#projects">Projects</a>
      <a href="#contact">Contact</a>
    </nav>

    <div class="site-header__actions">
      <?php if ($me): ?>
        <a href="<?= $portalHome ?>" class="btn btn--amber btn--nav">My Portal</a>
        <a href="logout.php" class="btn btn--outline btn--nav">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn btn--outline btn--nav">Log In</a>
        <a href="register.php" class="btn btn--amber btn--nav">Register</a>
      <?php endif; ?>
    </div>

    <button onclick="toggleMenu()" class="menu-toggle" aria-label="Menu">
      <iconify-icon icon="lucide:menu" width="24"></iconify-icon>
    </button>
  </div>

  <!-- Mobile menu -->
  <div id="mobileMenu" class="mobile-menu hidden">
    <div class="mobile-menu__links">
      <a href="index.php#about">Services</a>
      <a href="index.php#estimate">Estimate</a>
      <a href="index.php#projects">Projects</a>
      <a href="#contact">Contact</a>
    </div>
    <div class="mobile-menu__actions">
      <?php if ($me): ?>
        <a href="<?= $portalHome ?>" class="btn btn--amber btn--nav btn--nav-block">My Portal</a>
        <a href="logout.php" class="btn btn--outline btn--nav btn--nav-block">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn btn--outline btn--nav btn--nav-block">Log In</a>
        <a href="register.php" class="btn btn--amber btn--nav btn--nav-block">Register</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<?php endif; ?>
