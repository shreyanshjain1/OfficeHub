<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();
$id = Validator::int($_GET['id'] ?? null, 1);
if (!$id) { http_response_code(400); echo "Bad request"; exit; }

$a = DB::fetchOne("SELECT * FROM attachments WHERE id=? LIMIT 1", "i", [$id]);
if (!$a) { http_response_code(404); echo "Not found"; exit; }

$req = DB::fetchOne("SELECT * FROM requests WHERE id=? LIMIT 1", "i", [(int)$a['request_id']]);
if (!$req || !Policy::canDownloadAttachment($user, $req)) {
  Audit::log($user['id'], 'AUTHZ_DENY', 'attachment', $id, ['reason'=>'download_policy']);
  http_response_code(403); echo "Forbidden"; exit;
}

$dir = Uploads::ensureDir();
$path = $dir . DIRECTORY_SEPARATOR . $a['stored_name'];
if (!is_file($path)) { http_response_code(404); echo "Not found"; exit; }

Audit::log($user['id'], 'DOWNLOAD_OK', 'attachment', $id, ['request_id'=>(int)$a['request_id']]);

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $a['mime_type']);
$filename = str_replace(["\r","\n"], '', $a['original_name']);
header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
header('Content-Length: ' . filesize($path));

$fp = fopen($path, 'rb');
while (!feof($fp)) echo fread($fp, 8192);
fclose($fp);
exit;
