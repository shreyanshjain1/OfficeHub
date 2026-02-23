<?php
declare(strict_types=1);
$user = Auth::requireLogin();
Auth::requireRole($user, ['admin']);

$users = DB::fetchAll(
  "SELECT u.id, u.email, u.full_name, u.role, u.is_active, d.name AS department_name
   FROM users u LEFT JOIN departments d ON d.id=u.department_id
   ORDER BY u.created_at DESC"
);
$depts = DB::fetchAll("SELECT id, name FROM departments ORDER BY name ASC");
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h3 class="mb-1 fw-bold">User Management</h3>
    <div class="text-muted">Admin-only. Create users manually. No public signup.</div>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateUser">+ Create user</button>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr class="text-muted small"><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td class="fw-semibold"><?= Security::e($u['full_name']) ?></td>
              <td><?= Security::e($u['email']) ?></td>
              <td><span class="badge text-bg-secondary"><?= Security::e($u['role']) ?></span></td>
              <td><?= Security::e($u['department_name'] ?? '—') ?></td>
              <td><?php if ((int)$u['is_active'] === 1): ?><span class="badge text-bg-success">Active</span><?php else: ?><span class="badge text-bg-danger">Disabled</span><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalCreateUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="/actions/admin_user_create.php">
      <input type="hidden" name="csrf_token" value="<?= Security::e(Auth::csrfToken($user)) ?>">
      <div class="modal-header"><h5 class="modal-title">Create user</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Full name</label><input class="form-control" name="full_name" required maxlength="120"></div>
        <div class="mb-2"><label class="form-label">Email</label><input class="form-control" name="email" type="email" required maxlength="190"></div>
        <div class="mb-2"><label class="form-label">Role</label>
          <select class="form-select" name="role" required>
            <option value="employee">employee</option>
            <option value="manager">manager</option>
            <option value="admin">admin</option>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Department</label>
          <select class="form-select" name="department_id">
            <option value="">None</option>
            <?php foreach ($depts as $d): ?><option value="<?= (int)$d['id'] ?>"><?= Security::e($d['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Temporary password</label><input class="form-control" name="password" type="password" required minlength="8" maxlength="72"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Create</button></div>
    </form>
  </div>
</div>
