<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_role('client');

if (is_post()) {
  csrf_check();
  switch ($_POST['action'] ?? '') {
    case 'profile':
      $name  = trim($_POST['full_name'] ?? '');
      $email = strtolower(trim($_POST['email'] ?? ''));
      if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter your name and a valid email.');
      } elseif (q('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$email, $me['id']])->fetchColumn()) {
        flash('error', 'That email is already used by another account.');
      } else {
        q('UPDATE users SET full_name = ?, email = ?, phone = ?, company = ?, billing_address = ? WHERE id = ?', [
          mb_substr($name, 0, 120), $email,
          trim($_POST['phone'] ?? '') ?: null, trim($_POST['company'] ?? '') ?: null, trim($_POST['billing_address'] ?? '') ?: null,
          $me['id']]);
        flash('success', 'Profile saved.');
      }
      break;

    case 'password':
      $new = $_POST['new_password'] ?? '';
      if (!password_verify($_POST['current_password'] ?? '', $me['password_hash'])) flash('error', 'Your current password is incorrect.');
      elseif (strlen($new) < 8)                                                  flash('error', 'Your new password must be at least 8 characters.');
      elseif ($new !== ($_POST['confirm_password'] ?? ''))                       flash('error', 'The new passwords do not match.');
      else {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        session_regenerate_id(true);
        flash('success', 'Password updated.');
      }
      break;

    case 'notifications':
      q('UPDATE users SET notify_booking = ?, notify_invoice = ?, notify_sms = ? WHERE id = ?', [
        (int)!empty($_POST['notify_booking']), (int)!empty($_POST['notify_invoice']), (int)!empty($_POST['notify_sms']), $me['id']]);
      flash('success', 'Notification preferences saved.');
      break;
  }
  redirect('account-settings.php');
}

$notifications = [
  ['notify_booking', 'Booking updates',    'Get notified when a booking is confirmed, rescheduled, or completed.'],
  ['notify_invoice', 'Invoice reminders',  'Receive a reminder before an invoice is due.'],
  ['notify_sms',     'SMS dispatch alerts','Text messages when equipment is on its way to your site.'],
];

$pageTitle = 'Account Settings | C.D. Zaplan Trucking';
$portal = 'client'; $active = 'settings';
require_once __DIR__ . '/includes/portal-header.php';
?>
<div class="settings">
<h1 class="page-title">Account Settings</h1>
<p class="page-sub">Manage your profile, password, and notifications.</p>

<div class="stack">

  <!-- Profile -->
  <section class="card">
    <div class="card__head card__head--ruled">
      <h2 class="card__title">Profile</h2>
      <p class="card__sub">This information appears on your bookings and invoices.</p>
    </div>
    <form method="post" class="card__body">
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <div class="profile-photo">
        <span class="avatar avatar--navy avatar--xl"><?= e(initials($me['full_name'])) ?></span>
        <div>
          <div class="strong"><?= e($me['full_name']) ?></div>
          <p class="profile-photo__hint">Client since <?= fdate($me['created_at'], 'F Y') ?></p>
        </div>
      </div>
      <div class="form-grid form-grid--roomy form-grid--two">
        <div><label class="label" for="pfName">Full Name</label><input id="pfName" name="full_name" type="text" required maxlength="120" value="<?= e($me['full_name']) ?>" class="field"></div>
        <div><label class="label" for="pfEmail">Email</label><input id="pfEmail" name="email" type="email" required value="<?= e($me['email']) ?>" class="field"></div>
        <div><label class="label" for="pfPhone">Phone</label><input id="pfPhone" name="phone" type="tel" maxlength="30" value="<?= e($me['phone']) ?>" class="field"></div>
        <div><label class="label" for="pfCompany">Company</label><input id="pfCompany" name="company" type="text" maxlength="150" value="<?= e($me['company']) ?>" class="field"></div>
        <div class="field-span"><label class="label" for="pfAddr">Billing Address</label><input id="pfAddr" name="billing_address" type="text" maxlength="255" value="<?= e($me['billing_address']) ?>" class="field"></div>
      </div>
      <div class="form-actions"><button type="submit" class="btn btn--amber btn--lg">Save Changes</button></div>
    </form>
  </section>

  <!-- Password -->
  <section class="card">
    <div class="card__head card__head--ruled">
      <h2 class="card__title">Password</h2>
      <p class="card__sub">Use at least 8 characters.</p>
    </div>
    <form method="post" class="card__body">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <div class="form-grid form-grid--roomy form-grid--two">
        <div class="field-half">
          <label class="label" for="pwCurrent">Current Password</label>
          <div class="field-wrap"><input id="pwCurrent" name="current_password" type="password" required autocomplete="current-password" placeholder="Enter current password" class="field field--password">
            <button type="button" onclick="togglePassword(this)" class="password-toggle" aria-label="Show password"><iconify-icon icon="lucide:eye" width="16"></iconify-icon></button></div>
        </div>
        <div>
          <label class="label" for="pwNew">New Password</label>
          <div class="field-wrap"><input id="pwNew" name="new_password" type="password" required minlength="8" autocomplete="new-password" placeholder="Create a new password" class="field field--password">
            <button type="button" onclick="togglePassword(this)" class="password-toggle" aria-label="Show password"><iconify-icon icon="lucide:eye" width="16"></iconify-icon></button></div>
        </div>
        <div>
          <label class="label" for="pwConfirm">Confirm New Password</label>
          <div class="field-wrap"><input id="pwConfirm" name="confirm_password" type="password" required autocomplete="new-password" placeholder="Re-enter new password" class="field field--password">
            <button type="button" onclick="togglePassword(this)" class="password-toggle" aria-label="Show password"><iconify-icon icon="lucide:eye" width="16"></iconify-icon></button></div>
        </div>
      </div>
      <div class="form-actions"><button type="submit" class="btn btn--amber btn--lg">Update Password</button></div>
    </form>
  </section>

  <!-- Notifications: saves as soon as a switch is flipped -->
  <section class="card">
    <div class="card__head card__head--ruled">
      <h2 class="card__title">Notifications</h2>
      <p class="card__sub">Choose how we keep you updated.</p>
    </div>
    <form method="post" class="setting-list" id="notifyForm">
      <?= csrf_field() ?><input type="hidden" name="action" value="notifications">
      <?php foreach ($notifications as [$field, $title, $text]): ?>
      <label class="setting">
        <span>
          <span class="setting__title"><?= $title ?></span>
          <span class="setting__text"><?= $text ?></span>
        </span>
        <span class="switch">
          <input type="checkbox" name="<?= $field ?>" value="1" class="switch__input visually-hidden" <?= $me[$field] ? 'checked' : '' ?> onchange="this.form.submit()">
          <span class="switch__track"></span>
          <span class="switch__thumb"></span>
        </span>
      </label>
      <?php endforeach; ?>
    </form>
  </section>

</div>
</div>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
