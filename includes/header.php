<?php
/*
 * Shared <head> + public navigation.
 * Set these BEFORE including:
 *   $pageTitle        string
 *   $hidePublicHeader true on login / register / admin / dashboard
 *   $isLoggedIn       true to show the "REGISTER + LOGOUT" nav from the Figma
 */
require_once __DIR__ . '/helpers.php';
$pageTitle        = $pageTitle        ?? 'C.D. Zaplan Trucking';
$hidePublicHeader = $hidePublicHeader ?? false;
$isLoggedIn       = $isLoggedIn       ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script>
    document.documentElement.classList.add('js');
    // Failsafe: if the animation script doesn't load, show all content instead of leaving it hidden
    setTimeout(function () { if (!window.__motionReady) document.documentElement.classList.remove('js'); }, 2500);
  </script>
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <!-- Icons (Iconify). Everything else is plain CSS in assets/css/style.css -->
  <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
  <link rel="icon" href="assets/img/logo.png">
  <link rel="stylesheet" href="assets/css/style.css">
  <?= $extraHead ?? '' ?>
</head>
<body>

<?php if (!$hidePublicHeader): ?>
<header class="site-header">
  <div class="container site-header__bar">
    <a href="index.php" class="brand">
      <img src="assets/img/logo.png" alt="C.D. Zaplan Trucking logo">
      <span class="brand__name">C.D. Zaplan Trucking</span>
    </a>

    <nav class="nav">
      <a href="index.php#about">About</a>
      <a href="index.php#services">Services</a>
      <a href="index.php#reviews">Reviews</a>
      <a href="index.php#contact">Contact</a>
    </nav>

    <div class="header-actions">
      <?php if ($isLoggedIn): ?>
        <a href="register.php" class="btn btn--amber btn--label">Register</a>
        <a href="login.php" class="btn btn--outline btn--label">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn btn--outline btn--label">Log In</a>
        <a href="register.php" class="btn btn--amber btn--label">Register</a>
      <?php endif; ?>
    </div>

    <button onclick="toggleMenu()" class="menu-toggle" aria-label="Menu">
      <iconify-icon icon="lucide:menu" width="24"></iconify-icon>
    </button>
  </div>

  <!-- Mobile menu -->
  <div id="mobileMenu" class="mobile-menu hidden">
    <div class="mobile-menu__links">
      <a href="index.php#about">About</a>
      <a href="index.php#services">Services</a>
      <a href="index.php#reviews">Reviews</a>
      <a href="index.php#contact">Contact</a>
    </div>
    <div class="mobile-menu__actions">
      <a href="login.php" class="btn btn--outline btn--label">Log In</a>
      <a href="register.php" class="btn btn--amber btn--label">Register</a>
    </div>
  </div>
</header>
<?php endif; ?>
