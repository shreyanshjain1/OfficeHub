<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
Security::requirePost();
$user = Auth::requireLogin();
Auth::requireCsrf($user);
Auth::logout($user);
Response::redirect('/index.php?page=login', ['toast' => 'Signed out.']);
