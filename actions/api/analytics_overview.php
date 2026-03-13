<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

$user = Auth::requireLogin();
Auth::requireRole($user, ['manager', 'admin']);

$where = [];
$params = [];
$types = '';

if ($user['role'] === 'manager') {
    $where[] = '(r.department_id = ? OR r.assigned_to_user_id = ?)';
    $types .= 'ii';
    $params[] = (int)($user['department_id'] ?? 0);
    $params[] = (int)$user['id'];
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$baseSql = "
SELECT
    r.*,
    requester.full_name AS requester_name,
    d.name AS department_name
FROM requests r
JOIN users requester ON requester.id = r.requester_user_id
LEFT JOIN departments d ON d.id = r.department_id
{$whereSql}
";

$rows = DB::fetchAll($baseSql, $types, $params);

$statusCounts = [];
$priorityCounts = [];
$typeCounts = [];
$slaCounts = [
    'healthy' => 0,
    'at_risk' => 0,
    'breached' => 0,
    'resolved' => 0,
    'unknown' => 0,
];

$total = count($rows);
$active = 0;
$doneClosed = 0;
$urgentHigh = 0;

foreach ($rows as $row) {
    $status = (string)$row['status'];
    $priority = (string)$row['priority'];
    $type = (string)$row['type'];
    $slaBucket = AppHelper::slaBucket($row);

    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
    $priorityCounts[$priority] = ($priorityCounts[$priority] ?? 0) + 1;
    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
    $slaCounts[$slaBucket] = ($slaCounts[$slaBucket] ?? 0) + 1;

    if (in_array($status, ['OPEN', 'IN_REVIEW', 'APPROVED', 'IN_PROGRESS'], true)) {
        $active++;
    }

    if (in_array($status, ['DONE', 'CLOSED'], true)) {
        $doneClosed++;
    }

    if (in_array($priority, ['HIGH', 'URGENT'], true)) {
        $urgentHigh++;
    }
}

usort($rows, static function (array $a, array $b): int {
    $aAge = AppHelper::ageDays((string)$a['created_at']) ?? -1;
    $bAge = AppHelper::ageDays((string)$b['created_at']) ?? -1;
    return $bAge <=> $aAge;
});

$aging = [];
foreach ($rows as $row) {
    if (in_array((string)$row['status'], ['DONE', 'CLOSED', 'REJECTED'], true)) {
        continue;
    }

    $ageDays = AppHelper::ageDays((string)$row['created_at']);
    $slaBucket = AppHelper::slaBucket($row);

    $aging[] = [
        'id' => (int)$row['id'],
        'title' => (string)$row['title'],
        'requester_name' => (string)($row['requester_name'] ?? ''),
        'department_name' => (string)($row['department_name'] ?? ''),
        'status' => (string)$row['status'],
        'priority' => (string)$row['priority'],
        'status_badge_class' => AppHelper::statusBadgeClass((string)$row['status']),
        'priority_badge_class' => AppHelper::priorityBadgeClass((string)$row['priority']),
        'age_days' => $ageDays,
        'age_label' => $ageDays === null ? '—' : ($ageDays . ' day(s)'),
        'due_date_label' => AppHelper::formatDate($row['due_date']),
        'sla_label' => AppHelper::slaLabel($slaBucket),
        'sla_badge_class' => AppHelper::slaBadgeClass($slaBucket),
    ];

    if (count($aging) >= 10) {
        break;
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'summary' => [
        'total' => $total,
        'active' => $active,
        'done_closed' => $doneClosed,
        'urgent_high' => $urgentHigh,
        'at_risk_breached' => ($slaCounts['at_risk'] ?? 0) + ($slaCounts['breached'] ?? 0),
    ],
    'status_counts' => $statusCounts,
    'priority_counts' => $priorityCounts,
    'type_counts' => $typeCounts,
    'sla_counts' => $slaCounts,
    'aging' => $aging,
], JSON_UNESCAPED_SLASHES);