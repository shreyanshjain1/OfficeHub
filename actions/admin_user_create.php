<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
Security::requirePost();
$user = Auth::requireLogin();
Auth::requireRole($user, ['admin']);
Auth::requireCsrf($user);

$bucket = 'admin:usercreate:' . $user['id'];
$rl = RateLimit::checkAndHit($bucket, 20, 120);
if (!$rl['allowed']) Response::redirect('/index.php?page=admin_users', ['toast' => 'Too many actions. Try again shortly.']);

$allowedKeys = ['csrf_token','full_name','email','role','department_id','password'];
if (!Validator::rejectUnknownFields($_POST, $allowedKeys)) Response::redirect('/index.php?page=admin_users', ['toast' => 'Invalid form data.']);

$fullName = Validator::str($_POST['full_name'] ?? null, 2, 120);
$email = Validator::email($_POST['email'] ?? null);
$role = Validator::enum($_POST['role'] ?? null, ['employee','manager','admin']);
$deptId = Validator::int($_POST['department_id'] ?? null, 1, 1000000);
$pass = is_string($_POST['password'] ?? null) ? trim((string)$_POST['password']) : '';

if (!$fullName || !$email || !$role || strlen($pass) < 8 || strlen($pass) > 72) Response::redirect('/index.php?page=admin_users', ['toast' => 'Please check required fields.']);

if ($deptId !== null) {
  $exists = DB::fetchOne("SELECT id FROM departments WHERE id=? LIMIT 1", "i", [$deptId]);
  if (!$exists) $deptId = null;
}
$existing = DB::fetchOne("SELECT id FROM users WHERE email=? LIMIT 1", "s", [$email]);
if ($existing) Response::redirect('/index.php?page=admin_users', ['toast' => 'Email already exists.']);

$hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
DB::exec("INSERT INTO users (email, full_name, role, department_id, password_hash, is_active) VALUES (?, ?, ?, ?, ?, 1)","sssis",[$email,$fullName,$role,$deptId,$hash]);
$newId = DB::insertId();
Audit::log($user['id'], 'USER_CREATE', 'user', $newId, ['email'=>$email,'role'=>$role,'department_id'=>$deptId]);
Response::redirect('/index.php?page=admin_users', ['toast' => 'User created.']);
