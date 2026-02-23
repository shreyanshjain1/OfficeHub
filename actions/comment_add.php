<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
Security::requirePost();
$user = Auth::requireLogin();
Auth::requireCsrf($user);

$bucket = 'comment:add:user:' . $user['id'];
$rl = RateLimit::checkAndHit($bucket, 30, 120);
if (!$rl['allowed']) Response::redirect('/index.php?page=requests', ['toast' => 'Too many comments. Try again shortly.']);

$requestId = Validator::int($_POST['request_id'] ?? null, 1);
$body = Validator::str($_POST['body'] ?? null, 1, 5000);
$visibility = Validator::enum($_POST['visibility'] ?? 'ALL', ['ALL','MANAGERS_ADMINS']);

if (!$requestId || !$body) Response::redirect('/index.php?page=requests', ['toast' => 'Invalid comment.']);

$req = DB::fetchOne("SELECT * FROM requests WHERE id=? LIMIT 1", "i", [$requestId]);
if (!$req) Response::redirect('/index.php?page=requests', ['toast' => 'Not found.']);

if (!Policy::canComment($user, $req)) {
  Audit::log($user['id'], 'AUTHZ_DENY', 'request', $requestId, ['reason'=>'canComment']);
  Response::redirect('/index.php?page=requests', ['toast' => 'Not allowed.']);
}

if ($user['role'] === 'employee') $visibility = 'ALL';

DB::exec("INSERT INTO request_comments (request_id, author_user_id, body, visibility) VALUES (?, ?, ?, ?)","iiss",[$requestId,$user['id'],$body,$visibility]);
$commentId = DB::insertId();
Audit::log($user['id'], 'COMMENT_ADD', 'comment', $commentId, ['request_id'=>$requestId,'visibility'=>$visibility]);

if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
  require __DIR__ . '/upload.php';
  UploadAction::handleMultiUpload($user, $requestId, $commentId, $_FILES['attachments']);
}

Response::redirect('/index.php?page=request_view&id=' . $requestId, ['toast' => 'Comment posted.']);
