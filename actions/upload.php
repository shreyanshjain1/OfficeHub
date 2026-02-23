<?php
declare(strict_types=1);

final class UploadAction {
  public static function handleMultiUpload(array $user, int $requestId, ?int $commentId, array $files): void {
    $req = DB::fetchOne("SELECT * FROM requests WHERE id=? LIMIT 1", "i", [$requestId]);
    if (!$req) return;

    if (!Policy::canViewRequest($user, $req)) {
      Audit::log($user['id'], 'AUTHZ_DENY', 'request', $requestId, ['reason' => 'upload_canView']);
      return;
    }

    $dir = Uploads::ensureDir();

    $names = $files['name'] ?? [];
    $tmps = $files['tmp_name'] ?? [];
    $errs = $files['error'] ?? [];
    $sizes = $files['size'] ?? [];

    for ($i = 0; $i < count($names); $i++) {
      $orig = is_string($names[$i] ?? null) ? $names[$i] : 'file';
      $tmp = is_string($tmps[$i] ?? null) ? $tmps[$i] : '';
      $err = (int)($errs[$i] ?? UPLOAD_ERR_NO_FILE);
      $size = (int)($sizes[$i] ?? 0);

      if ($err === UPLOAD_ERR_NO_FILE) continue;
      if ($err !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
        Audit::log($user['id'], 'UPLOAD_FAIL', 'request', $requestId, ['error' => $err, 'name' => $orig]);
        continue;
      }

      if ($size <= 0 || $size > Uploads::MAX_BYTES) {
        Audit::log($user['id'], 'UPLOAD_REJECT', 'request', $requestId, ['reason' => 'size', 'size' => $size, 'name' => $orig]);
        continue;
      }

      $mime = Uploads::detectMime($tmp);
      if (!in_array($mime, Uploads::ALLOWED_MIME, true)) {
        Audit::log($user['id'], 'UPLOAD_REJECT', 'request', $requestId, ['reason' => 'mime', 'mime' => $mime, 'name' => $orig]);
        continue;
      }

      $stored = Uploads::randomStoredName();
      $sha = Uploads::sha256File($tmp);
      $dest = $dir . DIRECTORY_SEPARATOR . $stored;

      if (!move_uploaded_file($tmp, $dest)) {
        Audit::log($user['id'], 'UPLOAD_FAIL', 'request', $requestId, ['reason' => 'move_failed', 'name' => $orig]);
        continue;
      }
      @chmod($dest, 0640);

      DB::exec(
        "INSERT INTO attachments (request_id, comment_id, uploader_user_id, original_name, stored_name, mime_type, size_bytes, sha256)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        "iiisssis",
        [$requestId, $commentId, $user['id'], mb_substr($orig, 0, 255), $stored, $mime, $size, $sha]
      );
      $aid = DB::insertId();

      Audit::log($user['id'], 'UPLOAD_OK', 'attachment', $aid, ['request_id'=>$requestId,'comment_id'=>$commentId,'size'=>$size,'mime'=>$mime]);
    }
  }
}
