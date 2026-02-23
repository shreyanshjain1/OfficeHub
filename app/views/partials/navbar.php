<?php
declare(strict_types=1);
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="/index.php">
      <img src="/assets/img/logo.svg" width="28" height="28" alt="Logo">
      <span class="fw-semibold">Office Request Hub</span>
      <span class="badge text-bg-secondary ms-2 d-none d-md-inline"><?= Security::e($user['role']) ?></span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="/index.php?page=dashboard">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="/index.php?page=requests">Requests</a></li>
        <?php if ($user['role'] === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="/index.php?page=admin_users">Users</a></li>
        <?php endif; ?>
      </ul>

      <div class="d-flex align-items-center gap-2">
        <form method="post" action="/actions/theme.php" class="m-0">
          <input type="hidden" name="csrf_token" value="<?= Security::e(Auth::csrfToken($user)) ?>">
          <input type="hidden" name="toggle" value="1">
          <button class="btn btn-outline-secondary btn-sm" type="submit" title="Toggle theme">
            <span class="d-inline d-md-none">Theme</span>
            <span class="d-none d-md-inline">Light/Dark</span>
          </button>
        </form>

        <div class="text-end">
          <div class="small text-muted">Signed in as</div>
          <div class="fw-semibold"><?= Security::e($user['full_name']) ?></div>
        </div>

        <form method="post" action="/actions/logout.php" class="m-0">
          <input type="hidden" name="csrf_token" value="<?= Security::e(Auth::csrfToken($user)) ?>">
          <button class="btn btn-primary btn-sm" type="submit">Logout</button>
        </form>
      </div>
    </div>
  </div>
</nav>
