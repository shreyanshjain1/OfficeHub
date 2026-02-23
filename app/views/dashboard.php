<?php
declare(strict_types=1);
$user = Auth::requireLogin();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h3 class="mb-1 fw-bold">Dashboard</h3>
    <div class="text-muted">Overview, approvals, assignments, and recent activity</div>
  </div>
  <a class="btn btn-primary" href="/index.php?page=request_new">+ New request</a>
</div>

<div class="row g-3 mb-3" id="dashCards">
  <?php for ($i=0;$i<4;$i++): ?>
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="placeholder-glow">
            <span class="placeholder col-6"></span>
            <div class="mt-3"><span class="placeholder col-4"></span></div>
          </div>
        </div>
      </div>
    </div>
  <?php endfor; ?>
</div>

<div class="row g-3">
  <div class="col-12 col-xl-7">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h5 class="mb-0 fw-semibold">Recent activity</h5>
          <span class="badge text-bg-secondary">Live</span>
        </div>
        <div id="activityFeed" class="vstack gap-2">
          <?php for ($i=0;$i<6;$i++): ?>
            <div class="placeholder-glow">
              <span class="placeholder col-10"></span>
              <span class="placeholder col-6"></span>
            </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-5">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="fw-semibold mb-2">Quick links</h5>
        <div class="d-grid gap-2">
          <a class="btn btn-outline-secondary" href="/index.php?page=requests">Browse requests</a>
          <?php if ($user['role'] === 'admin'): ?>
            <a class="btn btn-outline-secondary" href="/index.php?page=admin_users">User management</a>
          <?php endif; ?>
          <?php if ($user['role'] !== 'employee'): ?>
            <a class="btn btn-outline-secondary" href="/index.php?page=requests&tab=needs_approval">Needs approval</a>
          <?php endif; ?>
          <a class="btn btn-outline-secondary" href="/index.php?page=requests&tab=my_assigned">My assigned</a>
        </div>
      </div>
    </div>
  </div>
</div>
