<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
Security::requirePost();
$user = Auth::requireLogin();
Auth::requireCsrf($user);
$bucket = 'req:create:user:' . $user['id'];
$rl = RateLimit::checkAndHit($bucket, 10, 120);
if (!$rl['allowed']) Response::redirect('/index.php?page=request_new', ['toast' => 'Too many requests. Try again shortly.']);
$allowedKeys = ['csrf_token','type','title','description','priority','department_id','due_date'];
if (!Validator::rejectUnknownFields($_POST, $allowedKeys)) Response::redirect('/index.php?page=request_new', ['toast' => 'Invalid form data.']);
$type = Validator::enum($_POST['type'] ?? null, ['IT','SUPPLIES','OFFICE','OTHER']);
$priority = Validator::enum($_POST['priority'] ?? null, ['LOW','MEDIUM','HIGH','URGENT']);
$title = Validator::str($_POST['title'] ?? null, 3, 140);
$desc = Validator::str($_POST['description'] ?? null, 10, 10000);
$due = Validator::date($_POST['due_date'] ?? null);
$deptId = Validator::int($_POST['department_id'] ?? null, 1, 1000000);
if ($deptId !== null) {
  $exists = DB::fetchOne("SELECT id FROM departments WHERE id=? LIMIT 1", "i", [$deptId]);
  if (!$exists) $deptId = null;
}
if ($deptId === null) $deptId = $user['department_id'];
if (!$type || !$priority || !$title || !$desc) Response::redirect('/index.php?page=request_new', ['toast' => 'Please check required fields.']);
DB::exec("INSERT INTO requests (requester_user_id, department_id, type, title, description, priority, due_date, status, status_updated_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, 'OPEN', NOW())","iisssss",[$user['id'],$deptId,$type,$title,$desc,$priority,$due]);
$requestId = DB::insertId();
Audit::log($user['id'], 'REQUEST_CREATE', 'request', $requestId, ['type'=>$type,'priority'=>$priority,'department_id'=>$deptId]);
if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) { require __DIR__ . '/upload.php'; UploadAction::handleMultiUpload($user, $requestId, null, $_FILES['attachments']); }
Response::redirect('/index.php?page=request_view&id=' . $requestId, ['toast' => 'Request created.']);
