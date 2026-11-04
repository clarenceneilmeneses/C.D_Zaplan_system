<?php
$pageTitle = 'Log In | C.D. Zaplan Trucking';
$hidePublicHeader = true;
$panelHeadline = 'Ready for<br>the next job.';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth">
  <div class="auth__top">
    <a href="index.php" class="brand">
      <img src="assets/img/logo.png" alt="C.D. Zaplan Trucking logo">
      <span class="brand__name">C.D. Zaplan Trucking</span>
    </a>
    <a href="index.php" class="auth__back">← Back to homepage</a>
  </div>

  <div class="auth__body">
    <?php require __DIR__ . '/includes/auth-panel.php'; ?>

    <div class="auth-form">
      <div class="eyebrow eyebrow--muted">Your projects. One account.</div>
      <h1>Welcome .</h1>
      <p class="auth-form__sub">Log in to manage your rentals, projects, and account.</p>

      <form onsubmit="handleLogin(event)" class="form">
        <div>
          <label class="label">Email</label>
          <input type="email" required placeholder="you@example.com" class="field">
        </div>
        <div>
          <label class="label">Password</label>
          <div class="input-wrap">
            <input type="password" required placeholder="Enter your password" class="field">
            <button type="button" onclick="togglePassword(this)" aria-label="Show password"><iconify-icon icon="lucide:eye" width="16"></iconify-icon></button>
          </div>
        </div>
        <div class="form-row">
          <label class="check"><input type="checkbox"> Remember Me</label>
          <a href="#" class="link-strong">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn--amber btn--block">Log In</button>
      </form>

      <p class="auth-form__switch">New to C.D. Zaplan? <a href="register.php" class="link-strong">Register</a></p>
    </div>
  </div>

  <div class="auth__footer">
    <div>© 2026 C.D. Zaplan Trucking. All rights reserved.</div>
    <button type="button" class="cdz-linkbtn" data-privacy-open aria-haspopup="dialog" aria-controls="cdzPrivacyModal">Privacy Policy</button>
  </div>
</div>
<?php require __DIR__ . '/includes/privacy-modal.php'; ?>
<?php require_once __DIR__ . '/includes/scripts.php'; ?>
