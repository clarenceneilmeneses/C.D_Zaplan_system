<?php
$pageTitle = 'Create Account | C.D. Zaplan Trucking';
$hidePublicHeader = true;
$panelHeadline = 'Build your next<br>project right.';
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
      <h1>Create your account</h1>
      <p class="auth-form__sub">Get started with reliable equipment and a crew you can count on.</p>

      <form onsubmit="handleRegister(event)" class="form">
        <div>
          <label class="label">Full Name</label>
          <input type="text" required placeholder="Enter your full name" class="field">
        </div>
        <div>
          <label class="label">Email</label>
          <input type="email" required placeholder="you@example.com" class="field">
        </div>
        <div>
          <label class="label">Password</label>
          <div class="input-wrap">
            <input type="password" required minlength="8" placeholder="Create a password" class="field">
            <button type="button" onclick="togglePassword(this)" aria-label="Show password"><iconify-icon icon="lucide:eye" width="16"></iconify-icon></button>
          </div>
          <p class="hint">Use at least 8 characters.</p>
        </div>
        <div>
          <label class="label">Confirm Password</label>
          <div class="input-wrap">
            <input type="password" required placeholder="Re-enter your password" class="field">
            <button type="button" onclick="togglePassword(this)" aria-label="Show password"><iconify-icon icon="lucide:eye" width="16"></iconify-icon></button>
          </div>
        </div>
        <button type="submit" class="btn btn--amber btn--block">Create Account</button>
      </form>

      <p class="auth-form__switch">Already have an account? <a href="login.php" class="link-strong">Log In</a></p>
    </div>
  </div>

  <div class="auth__footer">
    <div>© 2026 C.D. Zaplan Trucking. All rights reserved.</div>
    <button type="button" class="cdz-linkbtn" data-privacy-open aria-haspopup="dialog" aria-controls="cdzPrivacyModal">Privacy Policy</button>
  </div>
</div>
<?php require __DIR__ . '/includes/privacy-modal.php'; ?>
<?php require_once __DIR__ . '/includes/scripts.php'; ?>
