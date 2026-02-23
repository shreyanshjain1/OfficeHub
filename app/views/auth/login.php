<?php
declare(strict_types=1);
?>
<div class="login-shell w-100">
  <div class="row g-0 shadow-lg rounded-4 overflow-hidden bg-body">
    <div class="col-12 col-lg-6 p-4 p-lg-5 d-flex flex-column justify-content-center">
      <div class="d-flex align-items-center gap-2 mb-4">
        <img src="/assets/img/logo.svg" width="40" height="40" alt="Logo">
        <div>
          <div class="h4 fw-bold mb-0">Office Request Hub</div>
          <div class="text-muted">Secure internal request tracking</div>
        </div>
      </div>

      <div class="mb-3">
        <div class="badge text-bg-secondary">No public signup</div>
        <div class="small text-muted mt-2">Ask Admin to create your account.</div>
      </div>

      <form method="post" action="/actions/login.php" class="mt-2" novalidate>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <div class="input-icon">
            <span class="icon">✉️</span>
            <input name="email" type="email" class="form-control form-control-lg" required autocomplete="username">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <div class="input-icon">
            <span class="icon">🔒</span>
            <input name="password" type="password" class="form-control form-control-lg" required autocomplete="current-password">
          </div>
        </div>
        <button class="btn btn-primary btn-lg w-100" type="submit">Sign in</button>
      </form>

      <div class="mt-4 small text-muted">
        Security: HttpOnly session cookie • CSRF protected • Rate-limited login • Audit logs
      </div>
    </div>

    <div class="col-12 col-lg-6 p-4 p-lg-5 bg-grad d-none d-lg-flex flex-column justify-content-between">
      <div>
        <div class="h3 fw-bold">Premium workflow</div>
        <p class="text-muted">Submit requests with attachments, approvals, assignments, and a complete audit trail.</p>
      </div>
      <div class="small text-muted">
        Tip: Demo users are in <code>sql/002_seed.sql</code>.
      </div>
    </div>
  </div>
</div>
