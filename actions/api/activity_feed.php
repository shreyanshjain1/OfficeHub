<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
$user = Auth::requireLogin();
$rows = DB::fetchAll("SELECT action, target_type, target_id, created_at FROM audit_logs ORDER BY created_at DESC LIMIT 12");
$items = [];
foreach ($rows as $r) {
  $items[] = ['action'=>$r['action'], 'summary'=>$r['target_type'] . ($r['target_id'] ? (' #' . $r['target_id']) : ''), 'at'=>$r['created_at']];
}
Response::json(['ok'=>true,'items'=>$items]);
