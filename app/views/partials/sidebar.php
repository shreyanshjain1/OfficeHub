<?php
declare(strict_types=1);
$role = $user['role'];
?>
<div class="p-3">
  <div class="d-flex align-items-center gap-2 mb-3">
    <div class="avatar-circle"><?= Security::e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></div>
    <div>
      <div class="fw-semibold"><?= Security::e($user['full_name']) ?></div>
      <div class="small text-muted"><?= Security::e($user['email']) ?></div>
    </div>
  </div>

  <div class="list-group list-group-flush">
    <a class="list-group-item list-group-item-action" href="/index.php?page=dashboard">Dashboard</a>
    <a class="list-group-item list-group-item-action" href="/index.php?page=requests">Requests</a>
    <a class="list-group-item list-group-item-action" href="/index.php?page=request_new">New Request</a>
    <?php if ($role === 'admin'): ?>
      <a class="list-group-item list-group-item-action" href="/index.php?page=admin_users">User Management</a>
    <?php endif; ?>
  </div>

  <hr>
  <div class="small text-muted">
    Security: HttpOnly session cookie, CSRF tokens, RBAC + object policies, upload allow-list, audit logs.
  </div>
</div>
