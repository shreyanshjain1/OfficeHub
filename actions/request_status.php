<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
Security::requirePost();
$user = Auth::requireLogin();
Auth::requireCsrf($user);

$bucket = 'req:status:user:' . $user['id'];
$rl = RateLimit::checkAndHit($bucket, 20, 120);
if (!$rl['allowed']) Response::redirect('/index.php?page=requests', ['toast' => 'Too many actions. Try again shortly.']);

$id = Validator::int($_POST['id'] ?? null, 1);
$status = Validator::enum($_POST['status'] ?? null, ['OPEN','IN_REVIEW','APPROVED','REJECTED','IN_PROGRESS','DONE','CLOSED']);
$comment = is_string($_POST['comment'] ?? null) ? trim((string)$_POST['comment']) : '';

if (!$id || !$status) Response::redirect('/index.php?page=requests', ['toast' => 'Invalid request.']);

$req = DB::fetchOne("SELECT * FROM requests WHERE id=? LIMIT 1", "i", [$id]);
if (!$req) Response::redirect('/index.php?page=requests', ['toast' => 'Not found.']);

$curr = $req['status'];
$validTransitions = [
  'OPEN' => ['IN_REVIEW','CLOSED'],
  'IN_REVIEW' => ['APPROVED','REJECTED','OPEN'],
  'APPROVED' => ['IN_PROGRESS','DONE','CLOSED'],
  'REJECTED' => ['OPEN','CLOSED'],
  'IN_PROGRESS' => ['DONE','CLOSED'],
  'DONE' => ['CLOSED'],
  'CLOSED' => []
];
if (!in_array($status, $validTransitions[$curr] ?? [], true)) {
  Audit::log($user['id'], 'AUTHZ_DENY', 'request', $id, ['reason'=>'invalid_transition','from'=>$curr,'to'=>$status]);
  Response::redirect('/index.php?page=request_view&id=' . $id, ['toast' => 'Transition not allowed.']);
}

$allowed = false;
if (in_array($status, ['APPROVED','REJECTED'], true)) {
  $allowed = Policy::canApproveRequest($user, $req);
} else {
  if ($user['role'] === 'admin') $allowed = Policy::canViewRequest($user, $req);
  if ($user['role'] === 'employee') $allowed = Policy::canEditRequest($user, $req);
  if ($user['role'] === 'manager') $allowed = Policy::canViewRequest($user, $req) && in_array($curr, ['OPEN','IN_REVIEW'], true);
}

if (!$allowed) {
  Audit::log($user['id'], 'AUTHZ_DENY', 'request', $id, ['reason'=>'status_change_policy','to'=>$status]);
  Response::redirect('/index.php?page=request_view&id=' . $id, ['toast' => 'Not allowed.']);
}

$mgrReviewerId = null;
if (in_array($status, ['APPROVED','REJECTED'], true)) $mgrReviewerId = $user['id'];

DB::exec("UPDATE requests SET status=?, manager_reviewer_user_id=COALESCE(?, manager_reviewer_user_id), status_updated_at=NOW(), updated_at=NOW() WHERE id=?","sii",[$status,$mgrReviewerId,$id]);
Audit::log($user['id'], 'REQUEST_STATUS', 'request', $id, ['from'=>$curr,'to'=>$status]);

if ($comment !== '') {
  DB::exec("INSERT INTO request_comments (request_id, author_user_id, body, visibility) VALUES (?, ?, ?, 'ALL')","iis",[$id,$user['id'],mb_substr($comment,0,5000)]);
  $cid = DB::insertId();
  Audit::log($user['id'], 'COMMENT_ADD', 'comment', $cid, ['request_id'=>$id,'visibility'=>'ALL']);
}

Response::redirect('/index.php?page=request_view&id=' . $id, ['toast' => 'Status updated.']);
