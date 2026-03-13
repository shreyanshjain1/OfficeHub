<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$user = Auth::currentUser();

$page = $_GET['page'] ?? '';
if (!is_string($page)) {
    $page = '';
}

$routes = [
    '' => 'dashboard',
    'login' => 'auth/login',
    'dashboard' => 'dashboard',
    'requests' => 'requests/list',
    'request_new' => 'requests/new',
    'request_view' => 'requests/view',
    'admin_users' => 'admin/users',
    'analytics' => 'analytics',
];

if (!$user) {
    $page = 'login';
}

$viewKey = $routes[$page] ?? null;
if ($viewKey === null) {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

$viewFile = __DIR__ . '/../app/views/' . $viewKey . '.php';
if (!is_file($viewFile)) {
    http_response_code(500);
    echo 'View missing';
    exit;
}

require __DIR__ . '/../app/views/layout.php';