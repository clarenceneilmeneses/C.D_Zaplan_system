<?php /* Expects $registeredUsers. Optional: $usersSub, $showJoined */ ?>
<section class="card">
  <div class="card__head">
    <div>
      <h2 class="card__title">User Management</h2>
      <p class="card__sub"><?= $usersSub ?? '48 registered users · 4 recent accounts' ?></p>
    </div>
    <button onclick="handleAddUser()" class="btn btn--amber btn--md">
      <iconify-icon icon="lucide:plus" width="16"></iconify-icon> Add User
    </button>
  </div>
  <div class="table-scroll">
    <table class="data-table" style="--min:640px">
      <thead>
        <tr>
          <th>User Name</th><th>Email</th><th>Role</th><th>Status</th>
          <?php if (!empty($showJoined)): ?><th>Registered</th><?php endif; ?>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($registeredUsers as $u): ?>
        <tr>
          <td><div class="person"><span class="person__avatar"><?= initials($u['name']) ?></span><span class="person__name"><?= e($u['name']) ?></span></div></td>
          <td><?= e($u['email']) ?></td>
          <td><?= e($u['role']) ?></td>
          <td><?= pill($u['status']) ?></td>
          <?php if (!empty($showJoined)): ?><td class="cell-muted"><?= e($u['joined']) ?></td><?php endif; ?>
          <td><div class="row-actions">
            <a href="#" class="link-edit">Edit</a>
            <button class="icon-btn" aria-label="More"><iconify-icon icon="lucide:more-horizontal" width="16"></iconify-icon></button>
          </div></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
