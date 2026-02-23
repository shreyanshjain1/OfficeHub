<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
$user = Auth::requireLogin();

$q = is_string($_GET['q'] ?? '') ? trim((string)$_GET['q']) : '';
$status = is_string($_GET['status'] ?? '') ? (string)$_GET['status'] : '';
$type = is_string($_GET['type'] ?? '') ? (string)$_GET['type'] : '';
$priority = is_string($_GET['priority'] ?? '') ? (string)$_GET['priority'] : '';
$tab = is_string($_GET['tab'] ?? 'all') ? (string)$_GET['tab'] : 'all';

$status = in_array($status, ['OPEN','IN_REVIEW','APPROVED','REJECTED','IN_PROGRESS','DONE','CLOSED'], true) ? $status : '';
$type = in_array($type, ['IT','SUPPLIES','OFFICE','OTHER'], true) ? $type : '';
$priority = in_array($priority, ['LOW','MEDIUM','HIGH','URGENT'], true) ? $priority : '';
$tab = in_array($tab, ['all','needs_approval','my_assigned'], true) ? $tab : 'all';

$where = [];
$params = [];
$types = '';

if ($user['role'] === 'employee') { $where[]="r.requester_user_id=?"; $types.="i"; $params[]=$user['id']; }
elseif ($user['role'] === 'manager') { $where[]="r.department_id=?"; $types.="i"; $params[]=(int)($user['department_id'] ?? 0); }

if ($tab === 'needs_approval' && $user['role'] !== 'employee') $where[]="r.status='IN_REVIEW'";
if ($tab === 'my_assigned') { $where[]="r.assigned_to_user_id=?"; $types.="i"; $params[]=$user['id']; }

if ($status !== '') { $where[]="r.status=?"; $types.="s"; $params[]=$status; }
if ($type !== '') { $where[]="r.type=?"; $types.="s"; $params[]=$type; }
if ($priority !== '') { $where[]="r.priority=?"; $types.="s"; $params[]=$priority; }

if ($q !== '') {
  $where[]="(r.title LIKE ? OR r.description LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
  $like='%'.$q.'%';
  $types.="ssss";
  $params += [$like,$like,$like,$like];
}

$sql = "SELECT r.id, r.title, r.status, r.type, r.priority, r.created_at, u.full_name AS requester_name
        FROM requests r JOIN users u ON u.id=r.requester_user_id";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY r.created_at DESC LIMIT 200";

$items = DB::fetchAll($sql, $types, $params);
Response::json(['ok'=>true,'items'=>$items]);
