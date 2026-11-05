<?php
/*
 * Expects $registeredUsers (rows from the users table) and $userCount.
 * Optional: $usersSub, $showJoined, $userActions (true on user-management.php to show Add/Edit),
 *           $usersPager (from paginate(), adds search, role filter and page links).
 */
$userActions = $userActions ?? false;
$usersPager = $usersPager ?? null;
?>
<section class="card">
  <div class="card__head">
    <div>
      <h2 class="card__title">User Management</h2>
      <p class="card__sub"><?= e($usersSub ?? ($userCount . ' registered users · ' . count($registeredUsers) . ' most recent')) ?></p>
    </div>
    <?php if ($userActions): ?>
    <button onclick="openUserForm()" class="btn btn--amber btn--shrink">
      <iconify-icon icon="lucide:plus" width="16"></iconify-icon> Add User
    </button>
    <?php else: ?>
    <a href="user-management.php" class="btn btn--outline btn--shrink">Manage Users</a>
    <?php endif; ?>
  </div>
  <?php if ($usersPager): $roleNow = $_GET['role'] ?? ''; ?>
  <div class="toolbar toolbar--flush">
    <?= list_search('Search name, email, phone or company…') ?>
    <select onchange="location.href = this.value" class="select" aria-label="Role">
      <?php foreach (['' => 'Role: All', 'client' => 'Clients', 'admin' => 'Admins'] as $val => $label): ?>
      <option value="<?= e(url_with(['role' => $val ?: null, 'page' => null])) ?>" <?= $roleNow === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
  <div class="table-wrap">
    <table class="table table--min-640">
      <thead>
        <tr>
          <th>User Name</th><th>Email</th><th>Role</th>
          <th>Status</th><?php if (!empty($showJoined)): ?><th>Registered</th><?php endif; ?><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($registeredUsers as $u): ?>
        <tr>
          <td><div class="person">
            <span class="avatar"><?= e(initials($u['full_name'])) ?></span>
            <span class="cell-title"><?= e($u['full_name']) ?></span></div></td>
          <td><?= e($u['email']) ?></td>
          <td><?= $u['role'] === 'admin' ? 'Admin' : 'Client' ?></td>
          <td><?= pill($u['status']) ?></td>
          <?php if (!empty($showJoined)): ?><td class="td-muted"><?= fdate($u['created_at']) ?></td><?php endif; ?>
          <td><div class="row-actions">
            <?php if ($userActions): ?>
            <button type="button" class="link-underline" data-user="<?= e(json_encode([
              'id' => (int)$u['id'], 'full_name' => $u['full_name'], 'email' => $u['email'],
              'role' => $u['role'], 'status' => $u['status'], 'phone' => $u['phone']])) ?>"
              onclick="openUserForm(JSON.parse(this.dataset.user))">Edit</button>
            <?php else: ?>
            <a href="user-management.php" class="link-underline">Edit</a>
            <?php endif; ?>
          </div></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$registeredUsers): ?>
        <tr><td colspan="6" class="empty">No users match your filters.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($usersPager) echo pagination($usersPager, 'users'); ?>
</section>
