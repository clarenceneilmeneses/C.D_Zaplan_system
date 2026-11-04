<?php
$pageTitle = 'Account Settings | C.D. Zaplan Trucking';
$portal = 'client'; $active = 'settings';
require_once __DIR__ . '/includes/portal-header.php';

$notifications = [
  ['Booking updates',    'Get notified when a booking is confirmed, rescheduled, or completed.', true],
  ['Invoice reminders',  'Receive a reminder before an invoice is due.',                          true],
  ['SMS dispatch alerts','Text messages when equipment is on its way to your site.',              false],
];
$eye = '<button type="button" onclick="togglePassword(this)" aria-label="Show password"><iconify-icon icon="lucide:eye" width="16"></iconify-icon></button>';
?>
<div class="settings">
  <div class="page-head">
    <div>
      <h1 class="page-title">Account Settings</h1>
      <p class="page-sub">Manage your profile, password, and notifications.</p>
    </div>
  </div>

  <div class="stack">

    <!-- Profile -->
    <section class="card">
      <div class="card__head card__head--line">
        <div><h2 class="card__title">Profile</h2><p class="card__sub">This information appears on your bookings and invoices.</p></div>
      </div>
      <form onsubmit="event.preventDefault(); alert('Profile saved.');" class="card__body">
        <div class="profile-top">
          <span class="profile-avatar">SJ</span>
          <div>
            <button type="button" class="btn btn--outline btn--sm">Change photo</button>
            <p class="hint">JPG or PNG, up to 2 MB.</p>
          </div>
        </div>
        <div class="form-grid">
          <div><label class="label">Full Name</label><input type="text" value="Sarah Jenkins" class="field"></div>
          <div><label class="label">Email</label><input type="email" value="sarah.jenkins@example.com" class="field"></div>
          <div><label class="label">Phone</label><input type="tel" value="09171234567" class="field"></div>
          <div><label class="label">Company</label><input type="text" value="Jenkins Commercial Development" class="field"></div>
          <div class="span-2"><label class="label">Billing Address</label><input type="text" value="Dagupan City, Pangasinan" class="field"></div>
        </div>
        <div class="form-actions"><button type="submit" class="btn btn--amber btn--lg">Save Changes</button></div>
      </form>
    </section>

    <!-- Password -->
    <section class="card">
      <div class="card__head card__head--line">
        <div><h2 class="card__title">Password</h2><p class="card__sub">Use at least 8 characters.</p></div>
      </div>
      <form onsubmit="event.preventDefault(); alert('Password updated.');" class="card__body">
        <div class="form-grid">
          <div class="span-2 half">
            <label class="label">Current Password</label>
            <div class="input-wrap"><input type="password" placeholder="Enter current password" class="field"><?= $eye ?></div>
          </div>
          <div>
            <label class="label">New Password</label>
            <div class="input-wrap"><input type="password" minlength="8" placeholder="Create a new password" class="field"><?= $eye ?></div>
          </div>
          <div>
            <label class="label">Confirm New Password</label>
            <div class="input-wrap"><input type="password" placeholder="Re-enter new password" class="field"><?= $eye ?></div>
          </div>
        </div>
        <div class="form-actions"><button type="submit" class="btn btn--amber btn--lg">Update Password</button></div>
      </form>
    </section>

    <!-- Notifications -->
    <section class="card">
      <div class="card__head card__head--line">
        <div><h2 class="card__title">Notifications</h2><p class="card__sub">Choose how we keep you updated.</p></div>
      </div>
      <?php foreach ($notifications as [$title, $text, $on]): ?>
      <label class="setting-row">
        <span>
          <span class="setting-row__title"><?= $title ?></span>
          <span class="setting-row__text"><?= $text ?></span>
        </span>
        <span class="switch">
          <input type="checkbox" <?= $on ? 'checked' : '' ?>>
          <span class="switch__track"></span>
          <span class="switch__knob"></span>
        </span>
      </label>
      <?php endforeach; ?>
    </section>

  </div>
</div>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
