<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

$user = Auth::requireLogin();

$q = is_string($_GET['q'] ?? '') ? trim((string)$_GET['q']) : '';
$status = is_string($_GET['status'] ?? '') ? (string)$_GET['status'] : '';
$type = is_string($_GET['type'] ?? '') ? (string)$_GET['type'] : '';
$priority = is_string($_GET['priority'] ?? '') ? (string)$_GET['priority'] : '';
$tab = is_string($_GET['tab'] ?? 'all') ? (string)$_GET['tab'] : 'all';
$sort = is_string($_GET['sort'] ?? 'newest') ? (string)$_GET['sort'] : 'newest';

$status = in_array($status, ['OPEN', 'IN_REVIEW', 'APPROVED', 'REJECTED', 'IN_PROGRESS', 'DONE', 'CLOSED'], true) ? $status : '';
$type = in_array($type, ['IT', 'SUPPLIES', 'OFFICE', 'OTHER'], true) ? $type : '';
$priority = in_array($priority, ['LOW', 'MEDIUM', 'HIGH', 'URGENT'], true) ? $priority : '';
$tab = in_array($tab, ['all', 'needs_approval', 'my_assigned'], true) ? $tab : 'all';
$sort = in_array($sort, ['newest', 'oldest', 'priority_desc', 'priority_asc', 'title_asc', 'title_desc', 'due_asc', 'due_desc'], true) ? $sort : 'newest';

$where = [];
$params = [];
$types = '';

if ($user['role'] === 'employee') {
    $where[] = 'r.requester_user_id = ?';
    $types .= 'i';
    $params[] = (int)$user['id'];
} elseif ($user['role'] === 'manager') {
    $where[] = '(r.department_id = ? OR r.assigned_to_user_id = ?)';
    $types .= 'ii';
    $params[] = (int)($user['department_id'] ?? 0);
    $params[] = (int)$user['id'];
}

if ($tab === 'my_assigned') {
    $where[] = 'r.assigned_to_user_id = ?';
    $types .= 'i';
    $params[] = (int)$user['id'];
}

if ($tab === 'needs_approval' && in_array($user['role'], ['manager', 'admin'], true)) {
    if ($user['role'] === 'manager') {
        $where[] = "r.status = 'OPEN' AND r.department_id = ?";
        $types .= 'i';
        $params[] = (int)($user['department_id'] ?? 0);
    } else {
        $where[] = "r.status IN ('OPEN','IN_REVIEW')";
    }
}

if ($q !== '') {
    $where[] = '(r.title LIKE ? OR r.description LIKE ? OR requester.full_name LIKE ? OR requester.email LIKE ?)';
    $like = '%' . $q . '%';
    $types .= 'ssss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($status !== '') {
    $where[] = 'r.status = ?';
    $types .= 's';
    $params[] = $status;
}

if ($type !== '') {
    $where[] = 'r.type = ?';
    $types .= 's';
    $params[] = $type;
}

if ($priority !== '') {
    $where[] = 'r.priority = ?';
    $types .= 's';
    $params[] = $priority;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$orderSql = match ($sort) {
    'oldest' => 'ORDER BY r.created_at ASC, r.id ASC',
    'priority_desc' => "ORDER BY FIELD(r.priority, 'URGENT', 'HIGH', 'MEDIUM', 'LOW'), r.created_at DESC",
    'priority_asc' => "ORDER BY FIELD(r.priority, 'LOW', 'MEDIUM', 'HIGH', 'URGENT'), r.created_at DESC",
    'title_asc' => 'ORDER BY r.title ASC, r.created_at DESC',
    'title_desc' => 'ORDER BY r.title DESC, r.created_at DESC',
    'due_asc' => 'ORDER BY (r.due_date IS NULL), r.due_date ASC, r.created_at DESC',
    'due_desc' => 'ORDER BY (r.due_date IS NULL), r.due_date DESC, r.created_at DESC',
    default => 'ORDER BY r.created_at DESC, r.id DESC',
};

$sql = "
SELECT
    r.*,
    requester.full_name AS requester_name,
    requester.email AS requester_email,
    assigned.full_name AS assigned_name,
    d.name AS department_name
FROM requests r
JOIN users requester ON requester.id = r.requester_user_id
LEFT JOIN users assigned ON assigned.id = r.assigned_to_user_id
LEFT JOIN departments d ON d.id = r.department_id
{$whereSql}
{$orderSql}
LIMIT 300
";

$rows = DB::fetchAll($sql, $types, $params);

$out = [];
foreach ($rows as $row) {
    $description = trim((string)$row['description']);
    $preview = mb_substr(preg_replace('/\s+/', ' ', $description), 0, 96);
    if (mb_strlen($description) > 96) {
        $preview .= '…';
    }

    $ageDays = AppHelper::ageDays((string)$row['created_at']);
    $slaBucket = AppHelper::slaBucket($row);

    $out[] = [
        'id' => (int)$row['id'],
        'title' => (string)$row['title'],
        'description_preview' => $preview,
        'requester_name' => (string)($row['requester_name'] ?? ''),
        'requester_email' => (string)($row['requester_email'] ?? ''),
        'department_name' => (string)($row['department_name'] ?? ''),
        'assigned_name' => (string)($row['assigned_name'] ?? ''),
        'status' => (string)$row['status'],
        'type' => (string)$row['type'],
        'priority' => (string)$row['priority'],
        'due_date' => $row['due_date'],
        'due_date_label' => AppHelper::formatDate($row['due_date']),
        'created_at' => (string)$row['created_at'],
        'created_at_label' => AppHelper::formatDateTime((string)$row['created_at']),
        'age_days' => $ageDays,
        'age_days_label' => $ageDays === null ? '—' : ($ageDays . ' day(s) old'),
        'sla_bucket' => $slaBucket,
        'sla_label' => AppHelper::slaLabel($slaBucket),
        'status_badge_class' => AppHelper::statusBadgeClass((string)$row['status']),
        'priority_badge_class' => AppHelper::priorityBadgeClass((string)$row['priority']),
        'type_badge_class' => AppHelper::typeBadgeClass((string)$row['type']),
        'sla_badge_class' => AppHelper::slaBadgeClass($slaBucket),
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'count' => count($out),
    'rows' => $out,
], JSON_UNESCAPED_SLASHES);