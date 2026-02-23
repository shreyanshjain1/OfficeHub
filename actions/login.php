<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
Security::requirePost();
$email = Validator::email($_POST['email'] ?? null);
$pass = $_POST['password'] ?? null;
if (!$email || !is_string($pass) || $pass === '' || strlen($pass) > 200) {
  Audit::log(null, 'LOGIN_FAIL', 'user', null, ['reason' => 'validation']);
  Response::redirect('/index.php?page=login', ['toast' => 'Invalid email or password']);
}
$res = Auth::login($email, $pass);
if (!$res['ok']) Response::redirect('/index.php?page=login', ['toast' => $res['error']]);
Response::redirect('/index.php?page=dashboard', ['toast' => 'Welcome back.']);
