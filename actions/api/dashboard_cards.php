<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
$user = Auth::requireLogin();
$items = [];
if ($user['role'] === 'employee') {
  $c1 = DB::fetchOne("SELECT COUNT(*) AS c FROM requests WHERE requester_user_id=?", "i", [$user['id']]);
  $c2 = DB::fetchOne("SELECT COUNT(*) AS c FROM requests WHERE requester_user_id=? AND status IN ('OPEN','IN_REVIEW')", "i", [$user['id']]);
  $c3 = DB::fetchOne("SELECT COUNT(*) AS c FROM requests WHERE requester_user_id=? AND status='IN_PROGRESS'", "i", [$user['id']]);
  $c4 = DB::fetchOne("SELECT COUNT(*) AS c FROM requests WHERE requester_user_id=? AND status IN ('DONE','CLOSED')", "i", [$user['id']]);
  $items = [
    ['label'=>'My total requests','value'=>(int)$c1['c'],'hint'=>'All-time'],
    ['label'=>'Open / In review','value'=>(int)$c2['c'],'hint'=>'Needs attention'],
    ['label'=>'In progress','value'=>(int)$c3['c'],'hint'=>'Being worked on'],
    ['label'=>'Done / Closed','value'=>(int)$c4['c'],'hint'=>'Resolved'],
  ];
} elseif ($user['role'] === 'manager') {
  $dept = $user['department_id'] ?? 0;
  $c1 = DB::fetchOne("SELECT COUNT(*) AS c FROM requests WHERE department_id=?", "i", [$dept]);
  $c2 = DB::fetchOne("SELECT COUNT(*) AS c FROM requests WHERE department_id=? AND status='IN_REVIEW'", "i", [$dept]);
  $c3 = DB::fetchOne("SELECT COUNT(*) AS c FROM requests WHERE department_id=? AND status='IN_PROGRESS'", "i", [$dept]);
  $c4 = DB::fetchOne("SELECT COUNT(*) AS c FROM requests WHERE department_id=? AND status IN ('REJECTED','CLOSED')", "i", [$dept]);
  $items = [
    ['label'=>'Department total','value'=>(int)$c1['c'],'hint'=>'All-time'],
    ['label'=>'Needs approval','value'=>(int)$c2['c'],'hint'=>'Review queue'],
    ['label'=>'In progress','value'=>(int)$c3['c'],'hint'=>'Assigned/active'],
    ['label'=>'Rejected / Closed','value'=>(int)$c4['c'],'hint'=>'Completed decisions'],
  ];
} else {
  $c1 = DB::fetchOne("SELECT COUNT(*) AS c FROM requests", "", []);
  $c2 = DB::fetchOne("SELECT COUNT(*) AS c FROM requests WHERE status='IN_REVIEW'", "", []);
  $c3 = DB::fetchOne("SELECT COUNT(*) AS c FROM requests WHERE assigned_to_user_id=?", "i", [$user['id']]);
  $c4 = DB::fetchOne("SELECT COUNT(*) AS c FROM requests WHERE status='IN_PROGRESS'", "", []);
  $items = [
    ['label'=>'Total requests','value'=>(int)$c1['c'],'hint'=>'All departments'],
    ['label'=>'Needs approval','value'=>(int)$c2['c'],'hint'=>'Manager queue'],
    ['label'=>'Assigned to me','value'=>(int)$c3['c'],'hint'=>'My workload'],
    ['label'=>'In progress','value'=>(int)$c4['c'],'hint'=>'Active tasks'],
  ];
}
Response::json(['ok'=>true,'items'=>$items]);
