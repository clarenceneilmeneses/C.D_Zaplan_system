<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($u = current_user()) redirect($u['role'] === 'admin' ? 'admin.php' : 'dashboard.php');

$errors = [];
if (is_post()) {
  csrf_check();
  $name  = trim($_POST['full_name'] ?? '');
  $email = strtolower(trim($_POST['email'] ?? ''));
  $pass  = $_POST['password'] ?? '';

  if ($name === '' || mb_strlen($name) > 120)         $errors[] = 'Please enter your full name.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL))      $errors[] = 'Please enter a valid email address.';
  if (strlen($pass) < 8)                               $errors[] = 'Your password must be at least 8 characters.';
  if ($pass !== ($_POST['password_confirm'] ?? ''))    $errors[] = 'Passwords do not match.';
  if (!$errors && q('SELECT 1 FROM users WHERE email = ?', [$email])->fetchColumn()) {
    $errors[] = 'An account with this email already exists. Try logging in instead.';
  }

  if (!$errors) {
    // New sign-ups are always clients. Admins are created from the admin Users page.
    q('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, "client")',
      [$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
    $user = q('SELECT * FROM users WHERE id = ?', [db()->lastInsertId()])->fetch();
    // Link inquiries they sent before having an account
    q('UPDATE inquiries SET user_id = ? WHERE user_id IS NULL AND email = ?', [$user['id'], $email]);
    notify_admins('account', "New client account: $name", $email, 'user-management.php?q=' . urlencode($email));
    login_user($user);
    flash('success', 'Welcome, ' . explode(' ', $name)[0] . '! Your account is ready.');
    redirect(safe_next($_GET['next'] ?? null, 'dashboard.php'));
  }
}

$pageTitle = 'Create Account | C.D. Zaplan Trucking';
$hidePublicHeader = true;
$panelHeadline = 'Build your next<br>project right.';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth">
  <div class="auth__top">
    <a href="index.php" class="brand">
      <img src="assets/img/logo.png" alt="C.D. Zaplan Trucking logo" class="brand__logo">
      <span class="brand__name">C.D. Zaplan Trucking</span>
    </a>
    <a href="index.php" class="back-link">← Back to homepage</a>
  </div>

  <div class="auth__body">
    <?php require __DIR__ . '/includes/auth-panel.php'; ?>

    <div class="auth__form">
      <div class="eyebrow eyebrow--muted">Your projects. One account.</div>
      <h1 class="auth__title">Create your account</h1>
      <p class="auth__sub">Get started with reliable equipment and a crew you can count on.</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert--error"><iconify-icon icon="lucide:circle-alert" width="16"></iconify-icon><span><?= e($err) ?></span></div>
      <?php endforeach; ?>

      <form method="post" class="form form--auth" onsubmit="return checkPasswordsMatch(this)">
        <?= csrf_field() ?>
        <div>
          <label class="label" for="full_name">Full Name</label>
          <input id="full_name" name="full_name" type="text" required maxlength="120" autocomplete="name" value="<?= old('full_name') ?>" placeholder="Enter your full name" class="field">
        </div>
        <div>
          <label class="label" for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="email" value="<?= old('email') ?>" placeholder="you@example.com" class="field">
        </div>
        <div>
          <label class="label" for="password">Password</label>
          <div class="field-wrap">
            <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="Create a password" class="field field--password">
            <button type="button" onclick="togglePassword(this)" class="password-toggle" aria-label="Show password">
              <iconify-icon icon="lucide:eye" width="16"></iconify-icon>
            </button>
          </div>
          <p class="form-hint">Use at least 8 characters.</p>
        </div>
        <div>
          <label class="label" for="password_confirm">Confirm Password</label>
          <div class="field-wrap">
            <input id="password_confirm" name="password_confirm" type="password" required autocomplete="new-password" placeholder="Re-enter your password" class="field field--password">
            <button type="button" onclick="togglePassword(this)" class="password-toggle" aria-label="Show password">
              <iconify-icon icon="lucide:eye" width="16"></iconify-icon>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn--amber btn--block">Create Account</button>
      </form>

      <p class="auth__switch">
        Already have an account? <a href="login.php<?= isset($_GET['next']) ? '?next=' . urlencode($_GET['next']) : '' ?>" class="link-underline">Log In</a>
      </p>
      <p class="auth__legal">
        By creating an account, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
      </p>
    </div>
  </div>

  <div class="auth__footer">
    <div>© 2026 C.D. Zaplan Trucking. All rights reserved.</div>
    <div class="auth__footer-links"><a href="#">Privacy Policy</a> &nbsp;·&nbsp; <a href="#">Terms of Service</a></div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/scripts.php'; ?>
