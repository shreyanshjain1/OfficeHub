<?php
declare(strict_types=1);
$user = Auth::requireLogin();

$id = Validator::int($_GET['id'] ?? null, 1);
if (!$id) { http_response_code(400); echo "Bad request"; exit; }

$req = DB::fetchOne("SELECT * FROM requests WHERE id=? LIMIT 1", "i", [$id]);
if (!$req) { http_response_code(404); echo "Not found"; exit; }

if (!Policy::canViewRequest($user, $req)) {
  Audit::log($user['id'], 'AUTHZ_DENY', 'request', (int)$id, ['reason' => 'canViewRequest']);
  http_response_code(403); echo "Forbidden"; exit;
}

$requester = DB::fetchOne("SELECT full_name, email FROM users WHERE id=? LIMIT 1", "i", [(int)$req['requester_user_id']]);
$assigned = $req['assigned_to_user_id'] ? DB::fetchOne("SELECT full_name, email FROM users WHERE id=? LIMIT 1", "i", [(int)$req['assigned_to_user_id']]) : null;

$comments = DB::fetchAll(
  "SELECT c.*, u.full_name AS author_name, u.role AS author_role
   FROM request_comments c JOIN users u ON u.id=c.author_user_id
   WHERE c.request_id=? ORDER BY c.created_at ASC",
  "i",
  [$id]
);

$attachments = DB::fetchAll(
  "SELECT * FROM attachments WHERE request_id=? ORDER BY created_at DESC",
  "i",
  [$id]
);

function badgeStatus(string $s): string {
  $map = ['OPEN'=>'text-bg-primary','IN_REVIEW'=>'text-bg-warning','APPROVED'=>'text-bg-success','REJECTED'=>'text-bg-danger','IN_PROGRESS'=>'text-bg-info','DONE'=>'text-bg-success','CLOSED'=>'text-bg-secondary'];
  $cls = $map[$s] ?? 'text-bg-secondary';
  return '<span class="badge ' . $cls . '">' . Security::e($s) . '</span>';
}
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
  <div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <h3 class="mb-0 fw-bold">Request #<?= (int)$req['id'] ?></h3>
      <?= badgeStatus($req['status']) ?>
      <span class="badge text-bg-light border"><?= Security::e($req['type']) ?></span>
      <span class="badge text-bg-light border"><?= Security::e($req['priority']) ?></span>
    </div>
    <div class="text-muted mt-1"><?= Security::e($req['title']) ?></div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="/index.php?page=requests">Back</a>
    <?php if (Policy::canApproveRequest($user, $req)): ?>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalApprove">Approve</button>
      <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalReject">Reject</button>
    <?php endif; ?>
    <?php if (Policy::canAssignRequest($user, $req)): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAssign">Assign</button>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-xl-4">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="fw-semibold mb-3">Details</h5>
        <div class="vstack gap-2 small">
          <div><span class="text-muted">Requester:</span> <span class="fw-semibold"><?= Security::e($requester['full_name'] ?? 'Unknown') ?></span></div>
          <div><span class="text-muted">Assigned to:</span> <span class="fw-semibold"><?= Security::e($assigned['full_name'] ?? '—') ?></span></div>
          <div><span class="text-muted">Due date:</span> <span class="fw-semibold"><?= Security::e($req['due_date'] ?? '—') ?></span></div>
          <div><span class="text-muted">Created:</span> <span class="fw-semibold"><?= Security::e($req['created_at']) ?></span></div>
        </div>
        <hr>
        <div class="small text-muted">Description</div>
        <div class="mt-2"><?= nl2br(Security::e($req['description'])) ?></div>
      </div>
    </div>

    <div class="card shadow-sm border-0 mt-3">
      <div class="card-body">
        <h6 class="fw-semibold mb-2">Attachments</h6>
        <?php if (!$attachments): ?>
          <div class="text-muted small">No attachments.</div>
        <?php else: ?>
          <div class="vstack gap-2">
            <?php foreach ($attachments as $a): ?>
              <div class="d-flex align-items-center justify-content-between gap-2 border rounded-3 p-2">
                <div class="text-truncate">
                  <div class="fw-semibold text-truncate"><?= Security::e($a['original_name']) ?></div>
                  <div class="small text-muted"><?= Security::e($a['mime_type']) ?> • <?= (int)$a['size_bytes'] ?> bytes</div>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="/actions/download.php?id=<?= (int)$a['id'] ?>">Download</a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-8">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="fw-semibold mb-2">Timeline</h5>
        <div class="timeline">
          <?php foreach ($comments as $c): ?>
            <?php
              $canSee = true;
              if ($c['visibility'] === 'MANAGERS_ADMINS' && $user['role'] === 'employee') $canSee = false;
              if (!$canSee) continue;
            ?>
            <div class="timeline-item">
              <div class="dot"></div>
              <div class="content">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <div class="fw-semibold"><?= Security::e($c['author_name']) ?></div>
                  <span class="badge text-bg-light border"><?= Security::e($c['author_role']) ?></span>
                  <?php if ($c['visibility'] !== 'ALL'): ?><span class="badge text-bg-warning">Managers/Admins</span><?php endif; ?>
                  <div class="small text-muted"><?= Security::e($c['created_at']) ?></div>
                </div>
                <div class="mt-2"><?= nl2br(Security::e($c['body'])) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <hr class="my-3">

        <form method="post" action="/actions/comment_add.php" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= Security::e(Auth::csrfToken($user)) ?>">
          <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
          <div class="row g-2">
            <div class="col-12">
              <label class="form-label fw-semibold">Add comment</label>
              <textarea class="form-control" name="body" rows="3" required></textarea>
            </div>
            <div class="col-12 col-lg-7">
              <label class="form-label">Attachments (optional)</label>
              <input class="form-control" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,application/pdf,image/jpeg,image/png">
            </div>
            <div class="col-12 col-lg-5">
              <label class="form-label">Visibility</label>
              <select class="form-select" name="visibility">
                <option value="ALL">Visible to all viewers</option>
                <?php if ($user['role'] !== 'employee'): ?>
                  <option value="MANAGERS_ADMINS">Managers/Admins only</option>
                <?php endif; ?>
              </select>
            </div>
          </div>
          <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-primary" type="submit">Post comment</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalApprove" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="/actions/request_status.php">
      <input type="hidden" name="csrf_token" value="<?= Security::e(Auth::csrfToken($user)) ?>">
      <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
      <input type="hidden" name="status" value="APPROVED">
      <div class="modal-header"><h5 class="modal-title">Approve request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><label class="form-label">Comment (optional)</label><textarea class="form-control" name="comment" rows="3"></textarea></div>
      <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" type="submit">Approve</button></div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalReject" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="/actions/request_status.php">
      <input type="hidden" name="csrf_token" value="<?= Security::e(Auth::csrfToken($user)) ?>">
      <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
      <input type="hidden" name="status" value="REJECTED">
      <div class="modal-header"><h5 class="modal-title">Reject request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><label class="form-label">Reason</label><textarea class="form-control" name="comment" rows="3" required></textarea></div>
      <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" type="submit">Reject</button></div>
    </form>
  </div>
</div>

<?php if ($user['role'] === 'admin'): ?>
<?php $usersForAssign = DB::fetchAll("SELECT id, full_name, role FROM users WHERE is_active=1 ORDER BY role ASC, full_name ASC"); ?>
<div class="modal fade" id="modalAssign" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="/actions/request_assign.php">
      <input type="hidden" name="csrf_token" value="<?= Security::e(Auth::csrfToken($user)) ?>">
      <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
      <div class="modal-header"><h5 class="modal-title">Assign request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">Assign to</label>
        <select class="form-select" name="assigned_to_user_id" required>
          <option value="">Select user</option>
          <?php foreach ($usersForAssign as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= Security::e($u['full_name']) ?> (<?= Security::e($u['role']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <label class="form-label mt-3">Comment (optional)</label>
        <textarea class="form-control" name="comment" rows="3"></textarea>
      </div>
      <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Assign</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
