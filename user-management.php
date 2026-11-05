<?php
require_once __DIR__ . '/includes/bootstrap.php';
$admin = require_role('admin');

if (is_post()) {
  csrf_check();
  $id     = (int)($_POST['id'] ?? 0);
  $name   = trim($_POST['full_name'] ?? '');
  $email  = strtolower(trim($_POST['email'] ?? ''));
  $role   = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'client';
  $status = ($_POST['status'] ?? '') === 'Suspended' ? 'Suspended' : 'Active';
  $phone  = trim($_POST['phone'] ?? '') ?: null;
  $pass   = $_POST['password'] ?? '';

  if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Name and a valid email are required.');
  } elseif (q('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$email, $id])->fetchColumn()) {
    flash('error', "$email already has an account.");
  } elseif ($id === (int)$admin['id'] && ($role !== 'admin' || $status !== 'Active')) {
    flash('error', "You can't remove your own admin access or suspend yourself.");
  } elseif ((!$id && strlen($pass) < 8) || ($pass !== '' && strlen($pass) < 8)) {
    flash('error', 'Passwords must be at least 8 characters.');
  } elseif ($id) {
    q('UPDATE users SET full_name = ?, email = ?, role = ?, status = ?, phone = ? WHERE id = ?', [$name, $email, $role, $status, $phone, $id]);
    if ($pass !== '') q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $id]);
    flash('success', "$name updated" . ($pass !== '' ? ' and their password was reset.' : '.'));
  } else {
    q('INSERT INTO users (full_name, email, password_hash, role, status, phone) VALUES (?, ?, ?, ?, ?, ?)',
      [$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $status, $phone]);
    flash('success', "$name was added. Share their temporary password with them privately.");
  }
  redirect(url_with());
}

// Search box + global date range (registration date) + role filter, 10 per page
$role = in_array($_GET['role'] ?? '', ['admin', 'client'], true) ? $_GET['role'] : '';
[$w, $p] = where(like_any(['full_name', 'email', 'phone', 'company'], search_term()),
                 date_where('created_at'),
                 $role ? ['role = ?', [$role]] : ['', []]);
$usersPager = paginate("SELECT * FROM users $w ORDER BY role = 'admin' DESC, created_at DESC, id DESC", $p);
$registeredUsers = $usersPager['rows'];
$userCount = (int)q('SELECT COUNT(*) FROM users')->fetchColumn();
$clients = (int)q('SELECT COUNT(*) FROM users WHERE role = "client"')->fetchColumn();
$usersSub = "$userCount registered users · $clients clients · Manage roles and permissions";
$showJoined = true;
$userActions = true;

$pageTitle = 'User Management | C.D. Zaplan Trucking';
$portal = 'admin'; $active = 'users';
$dateFilter = true;
require_once __DIR__ . '/includes/portal-header.php';
?>
<h1 class="page-title">Users</h1>
<p class="page-sub page-sub--spaced">Registered client accounts and system permissions.</p>
<?php require __DIR__ . '/includes/users-card.php'; ?>

<div id="userModal" class="modal hidden" onclick="if (event.target === this) closeModal('userModal')">
  <div class="dialog dialog--padded">
    <div class="dialog__head">
      <h2 class="dialog__title" id="userTitle">Add User</h2>
      <button onclick="closeModal('userModal')" aria-label="Close" class="close-btn close-btn--plain"><iconify-icon icon="lucide:x" width="20"></iconify-icon></button>
    </div>
    <form method="post" id="userForm" class="form form--modal">
      <?= csrf_field() ?><input type="hidden" name="id">
      <div><label class="label">Full Name</label><input name="full_name" required maxlength="120" class="field"></div>
      <div class="form-grid form-grid--two">
        <div><label class="label">Email</label><input name="email" type="email" required class="field"></div>
        <div><label class="label">Phone</label><input name="phone" type="tel" maxlength="30" class="field"></div>
      </div>
      <div class="form-grid form-grid--two">
        <div><label class="label">Role</label><select name="role" class="field"><option value="client">Client</option><option value="admin">Admin</option></select></div>
        <div><label class="label">Status</label><select name="status" class="field"><option>Active</option><option>Suspended</option></select></div>
      </div>
      <div>
        <label class="label" id="userPassLabel">Temporary Password</label>
        <div class="field-wrap">
          <input name="password" type="password" minlength="8" autocomplete="new-password" class="field field--password">
          <button type="button" onclick="togglePassword(this)" class="password-toggle" aria-label="Show password"><iconify-icon icon="lucide:eye" width="16"></iconify-icon></button>
        </div>
        <p class="form-hint" id="userPassHint">At least 8 characters. Ask the user to change it after logging in.</p>
      </div>
      <button class="btn btn--amber btn--block">Save User</button>
    </form>
  </div>
</div>
<script>
function openUserForm(u) {
  const f = document.getElementById('userForm');
  f.reset();
  f.elements['id'].value = u ? u.id : '';
  if (u) ['full_name', 'email', 'phone', 'role', 'status'].forEach(k => { f.elements[k].value = u[k] ?? ''; });
  document.getElementById('userTitle').textContent = u ? 'Edit ' + u.full_name : 'Add User';
  document.getElementById('userPassLabel').textContent = u ? 'Reset Password (optional)' : 'Temporary Password';
  document.getElementById('userPassHint').textContent = u ? 'Leave blank to keep their current password.' : 'At least 8 characters. Ask the user to change it after logging in.';
  f.elements.password.required = !u;
  openModal('userModal');
}
</script>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>
