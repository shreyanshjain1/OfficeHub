<?php
declare(strict_types=1);

final class Uploads {
  public const MAX_BYTES = 5242880; // 5MB
  public const ALLOWED_MIME = ['image/jpeg','image/png','application/pdf'];

  public static function ensureDir(): string {
    $dir = Config::get('UPLOAD_DIR');
    if ($dir === '') $dir = dirname(__DIR__) . '/storage/uploads';
    if (!is_dir($dir)) { @mkdir($dir, 0750, true); }
    if (!is_dir($dir) || !is_writable($dir)) {
      throw new RuntimeException('Upload directory not writable.');
    }
    return rtrim($dir, DIRECTORY_SEPARATOR);
  }

  public static function detectMime(string $tmpPath): string {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->file($tmpPath) ?: 'application/octet-stream';
  }

  public static function randomStoredName(): string {
    return hash('sha256', Security::randomHex(32) . '|' . microtime(true));
  }

  public static function sha256File(string $tmpPath): string {
    return hash_file('sha256', $tmpPath) ?: '';
  }
}
