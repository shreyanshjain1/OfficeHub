<?php
declare(strict_types=1);
$user = Auth::requireLogin();
$tab = $_GET['tab'] ?? 'all';
if (!is_string($tab)) $tab = 'all';
$tab = in_array($tab, ['all','needs_approval','my_assigned'], true) ? $tab : 'all';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h3 class="mb-1 fw-bold">Requests</h3>
    <div class="text-muted">Search and filter your request queue</div>
  </div>
  <a class="btn btn-primary" href="/index.php?page=request_new">+ New request</a>
</div>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-lg-4">
        <label class="form-label">Search</label>
        <input id="fSearch" class="form-control" placeholder="Title, description, requester…">
      </div>
      <div class="col-6 col-lg-2">
        <label class="form-label">Status</label>
        <select id="fStatus" class="form-select">
          <option value="">All</option>
          <option>OPEN</option>
          <option>IN_REVIEW</option>
          <option>APPROVED</option>
          <option>REJECTED</option>
          <option>IN_PROGRESS</option>
          <option>DONE</option>
          <option>CLOSED</option>
        </select>
      </div>
      <div class="col-6 col-lg-2">
        <label class="form-label">Type</label>
        <select id="fType" class="form-select">
          <option value="">All</option>
          <option>IT</option>
          <option>SUPPLIES</option>
          <option>OFFICE</option>
          <option>OTHER</option>
        </select>
      </div>
      <div class="col-6 col-lg-2">
        <label class="form-label">Priority</label>
        <select id="fPriority" class="form-select">
          <option value="">All</option>
          <option>LOW</option>
          <option>MEDIUM</option>
          <option>HIGH</option>
          <option>URGENT</option>
        </select>
      </div>
      <div class="col-6 col-lg-2 d-grid">
        <button id="btnApply" class="btn btn-outline-primary">Apply</button>
      </div>
    </div>

    <hr class="my-3">

    <ul class="nav nav-pills gap-2">
      <li class="nav-item"><a class="nav-link <?= $tab==='all'?'active':'' ?>" href="/index.php?page=requests&tab=all">All</a></li>
      <li class="nav-item"><a class="nav-link <?= $tab==='my_assigned'?'active':'' ?>" href="/index.php?page=requests&tab=my_assigned">My assigned</a></li>
      <?php if ($user['role'] !== 'employee'): ?>
        <li class="nav-item"><a class="nav-link <?= $tab==='needs_approval'?'active':'' ?>" href="/index.php?page=requests&tab=needs_approval">Needs approval</a></li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr class="text-muted small">
            <th>ID</th><th>Title</th><th class="d-none d-md-table-cell">Requester</th><th>Status</th>
            <th class="d-none d-lg-table-cell">Type</th><th>Priority</th><th class="d-none d-lg-table-cell">Created</th><th></th>
          </tr>
        </thead>
        <tbody id="reqTbody">
          <?php for ($i=0;$i<6;$i++): ?>
            <tr class="placeholder-glow">
              <td><span class="placeholder col-6"></span></td>
              <td><span class="placeholder col-10"></span></td>
              <td class="d-none d-md-table-cell"><span class="placeholder col-8"></span></td>
              <td><span class="placeholder col-6"></span></td>
              <td class="d-none d-lg-table-cell"><span class="placeholder col-6"></span></td>
              <td><span class="placeholder col-6"></span></td>
              <td class="d-none d-lg-table-cell"><span class="placeholder col-8"></span></td>
              <td><span class="placeholder col-6"></span></td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>

    <div id="emptyStateWrap" class="d-none">
      <?php
        $actionHtml = '<a class="btn btn-primary" href="/index.php?page=request_new">Create your first request</a>';
        $title = 'No requests found';
        $subtitle = 'Try adjusting filters or create a new request.';
        require __DIR__ . '/../partials/empty_state.php';
      ?>
    </div>
  </div>
</div>

<script>
  window.__REQUESTS_TAB__ = <?= json_encode($tab, JSON_UNESCAPED_SLASHES) ?>;
</script>
