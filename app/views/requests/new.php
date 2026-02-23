<?php
declare(strict_types=1);
$user = Auth::requireLogin();
$depts = DB::fetchAll("SELECT id, name FROM departments ORDER BY name ASC");
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h3 class="mb-1 fw-bold">New Request</h3>
    <div class="text-muted">Create a request with optional attachments</div>
  </div>
  <a class="btn btn-outline-secondary" href="/index.php?page=requests">Back to list</a>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body p-3 p-lg-4">
    <form method="post" action="/actions/request_create.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= Security::e(Auth::csrfToken($user)) ?>">

      <div class="row g-3">
        <div class="col-12 col-lg-6">
          <label class="form-label">Type</label>
          <select class="form-select" name="type" required>
            <option value="IT">IT</option>
            <option value="SUPPLIES">Supplies</option>
            <option value="OFFICE">Office</option>
            <option value="OTHER">Other</option>
          </select>
        </div>
        <div class="col-12 col-lg-6">
          <label class="form-label">Priority</label>
          <select class="form-select" name="priority" required>
            <option value="LOW">Low</option>
            <option value="MEDIUM" selected>Medium</option>
            <option value="HIGH">High</option>
            <option value="URGENT">Urgent</option>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Title</label>
          <input class="form-control" name="title" maxlength="140" required>
        </div>

        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="5" required></textarea>
        </div>

        <div class="col-12 col-lg-6">
          <label class="form-label">Department (optional override)</label>
          <select class="form-select" name="department_id">
            <option value="">Use my department</option>
            <?php foreach ($depts as $d): ?>
              <option value="<?= (int)$d['id'] ?>"><?= Security::e($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-lg-6">
          <label class="form-label">Due date (optional)</label>
          <input class="form-control" type="date" name="due_date">
        </div>

        <div class="col-12">
          <label class="form-label">Attachments (jpeg/png/pdf, max 5MB each)</label>
          <input class="form-control" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,application/pdf,image/jpeg,image/png">
        </div>
      </div>

      <hr class="my-4">
      <div class="d-flex gap-2 justify-content-end">
        <a class="btn btn-outline-secondary" href="/index.php?page=requests">Cancel</a>
        <button class="btn btn-primary" type="submit">Create request</button>
      </div>
    </form>
  </div>
</div>
