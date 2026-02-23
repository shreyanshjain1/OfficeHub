<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
Security::requirePost();
$user = Auth::requireLogin();
Auth::requireCsrf($user);
$current = Security::getTheme();
$next = ($current === 'dark') ? 'light' : 'dark';
Security::setThemeCookie($next);
Audit::log($user['id'], 'THEME_SET', 'user', $user['id'], ['theme' => $next]);
Response::redirect($_SERVER['HTTP_REFERER'] ?? '/index.php?page=dashboard');
