<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Already logged in → straight to the right portal
if ($u = current_user()) redirect($u['role'] === 'admin' ? 'admin.php' : 'dashboard.php');

$error = '';
if (is_post()) {
  csrf_check();
  $email = strtolower(trim($_POST['email'] ?? ''));
  $user  = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();

  if (!$user || !password_verify($_POST['password'] ?? '', $user['password_hash'])) {
    $error = 'Incorrect email or password.';
  } elseif ($user['status'] !== 'Active') {
    $error = 'This account is suspended. Please contact C.D. Zaplan Trucking.';
  } else {
    login_user($user, !empty($_POST['remember']));
    $home = $user['role'] === 'admin' ? 'admin.php' : 'dashboard.php';
    // Admins never get sent into client pages by ?next= (and vice versa require_role handles it)
    redirect(safe_next($_GET['next'] ?? null, $home));
  }
}

$pageTitle = 'Log In | C.D. Zaplan Trucking';
$hidePublicHeader = true;
$panelHeadline = 'Ready for<br>the next job.';
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
      <h1 class="auth__title">Welcome back.</h1>
      <p class="auth__sub">Log in to manage your rentals, projects, and account.</p>

      <?= render_flash() ?>
      <?php if ($error): ?>
        <div class="alert alert--error"><iconify-icon icon="lucide:circle-alert" width="16"></iconify-icon><span><?= e($error) ?></span></div>
      <?php endif; ?>

      <form method="post" class="form form--auth">
        <?= csrf_field() ?>
        <div>
          <label class="label" for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="email" value="<?= old('email') ?>" placeholder="you@example.com" class="field">
        </div>
        <div>
          <label class="label" for="password">Password</label>
          <div class="field-wrap">
            <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Enter your password" class="field field--password">
            <button type="button" onclick="togglePassword(this)" class="password-toggle" aria-label="Show password">
              <iconify-icon icon="lucide:eye" width="16"></iconify-icon>
            </button>
          </div>
        </div>
        <div class="form-meta">
          <label class="check">
            <input type="checkbox" name="remember" value="1"> Remember Me
          </label>
          <button type="button" class="link-underline" onclick="document.getElementById('forgotNote').classList.toggle('hidden')">Forgot password?</button>
        </div>
        <p id="forgotNote" class="form-hint hidden">
          Call or text us at 09176757888 or email cdzaplantrucking@gmail.com. An administrator will reset your password from the Users page.
        </p>
        <button type="submit" class="btn btn--amber btn--block">Log In</button>
      </form>

      <p class="auth__switch">
        New to C.D. Zaplan? <a href="register.php<?= isset($_GET['next']) ? '?next=' . urlencode($_GET['next']) : '' ?>" class="link-underline">Register</a>
      </p>
    </div>
  </div>

  <div class="auth__footer">
    <div>© 2026 C.D. Zaplan Trucking. All rights reserved.</div>
    <div class="auth__footer-links"><a href="#">Privacy Policy</a> &nbsp;·&nbsp; <a href="#">Terms of Service</a></div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/scripts.php'; ?>
