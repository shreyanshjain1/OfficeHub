<?php

declare(strict_types=1);

$role = $user['role'];
$page = AppHelper::currentPage();

$items = [
    [
        'label' => 'Dashboard',
        'href' => '/index.php?page=dashboard',
        'page' => 'dashboard',
        'show' => true,
    ],
    [
        'label' => 'Requests',
        'href' => '/index.php?page=requests',
        'page' => 'requests',
        'show' => true,
    ],
    [
        'label' => 'New Request',
        'href' => '/index.php?page=request_new',
        'page' => 'request_new',
        'show' => true,
    ],
    [
        'label' => 'Analytics',
        'href' => '/index.php?page=analytics',
        'page' => 'analytics',
        'show' => in_array($role, ['manager', 'admin'], true),
    ],
    [
        'label' => 'User Management',
        'href' => '/index.php?page=admin_users',
        'page' => 'admin_users',
        'show' => $role === 'admin',
    ],
];
?>

<div class="p-3">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="avatar-circle">
            <?= AppHelper::h(AppHelper::avatarLetter((string)$user['full_name'])) ?>
        </div>
        <div class="min-w-0">
            <div class="fw-semibold text-truncate"><?= AppHelper::h((string)$user['full_name']) ?></div>
            <div class="small text-muted text-truncate"><?= AppHelper::h((string)$user['email']) ?></div>
            <div class="mt-1">
                <span class="badge text-bg-dark"><?= AppHelper::h(AppHelper::roleLabel((string)$role)) ?></span>
            </div>
        </div>
    </div>

    <div class="small text-uppercase fw-semibold text-muted mb-2">Workspace</div>

    <div class="list-group list-group-flush mb-3">
        <?php foreach ($items as $item): ?>
            <?php if (!$item['show']) continue; ?>
            <?php $active = $page === $item['page']; ?>
            <a
                class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?= $active ? 'active' : '' ?>"
                href="<?= AppHelper::h($item['href']) ?>"
            >
                <span><?= AppHelper::h($item['label']) ?></span>
                <?php if ($active): ?>
                    <span class="badge text-bg-light text-dark">Open</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="small text-uppercase fw-semibold text-muted mb-2">Project Strengths</div>
            <ul class="small mb-0 ps-3">
                <li>HttpOnly DB-backed sessions</li>
                <li>CSRF checks on state-changing routes</li>
                <li>RBAC + object-level authorization</li>
                <li>Attachment allow-list + audit trail</li>
                <li>Queue analytics and SLA visibility</li>
            </ul>
        </div>
    </div>
</div>