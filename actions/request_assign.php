<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
Security::requirePost();
$user = Auth::requireLogin();
Auth::requireCsrf($user);

$id = Validator::int($_POST['id'] ?? null, 1);
$assigneeId = Validator::int($_POST['assigned_to_user_id'] ?? null, 1);
$comment = is_string($_POST['comment'] ?? null) ? trim((string)$_POST['comment']) : '';

if (!$id || !$assigneeId) Response::redirect('/index.php?page=requests', ['toast' => 'Invalid assignment.']);
$req = DB::fetchOne("SELECT * FROM requests WHERE id=? LIMIT 1", "i", [$id]);
if (!$req) Response::redirect('/index.php?page=requests', ['toast' => 'Not found.']);

if (!Policy::canAssignRequest($user, $req)) {
  Audit::log($user['id'], 'AUTHZ_DENY', 'request', $id, ['reason'=>'canAssignRequest']);
  Response::redirect('/index.php?page=request_view&id='.$id, ['toast'=>'Not allowed.']);
}

$assignee = DB::fetchOne("SELECT id, is_active FROM users WHERE id=? LIMIT 1", "i", [$assigneeId]);
if (!$assignee || (int)$assignee['is_active'] !== 1) Response::redirect('/index.php?page=request_view&id='.$id, ['toast'=>'Invalid assignee.']);

DB::exec("UPDATE requests SET assigned_to_user_id=?, status=IF(status='OPEN','IN_PROGRESS',status), status_updated_at=NOW(), updated_at=NOW() WHERE id=?","ii",[$assigneeId,$id]);
Audit::log($user['id'], 'REQUEST_ASSIGN', 'request', $id, ['assigned_to'=>$assigneeId]);

if ($comment !== '') {
  DB::exec("INSERT INTO request_comments (request_id, author_user_id, body, visibility) VALUES (?, ?, ?, 'ALL')","iis",[$id,$user['id'],mb_substr($comment,0,5000)]);
  $cid = DB::insertId();
  Audit::log($user['id'], 'COMMENT_ADD', 'comment', $cid, ['request_id'=>$id,'visibility'=>'ALL']);
}
Response::redirect('/index.php?page=request_view&id=' . $id, ['toast' => 'Assigned successfully.']);
