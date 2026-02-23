<?php
declare(strict_types=1);

final class Config {
  private static array $cfg = [];

  public static function load(): void {
    if (self::$cfg) return;
    $envPath = dirname(__DIR__) . '/.env';
    $defaults = [
      'APP_ENV' => 'local',
      'APP_NAME' => 'Office Request Hub',
      'APP_BASE_URL' => '',
      'APP_SECRET' => 'change_me',
      'DB_HOST' => '127.0.0.1',
      'DB_NAME' => 'office_request_hub',
      'DB_USER' => 'root',
      'DB_PASS' => '',
      'DB_PORT' => '3306',
      'UPLOAD_DIR' => dirname(__DIR__) . '/storage/uploads',
      'COOKIE_SECURE' => '0',
      'COOKIE_SAMESITE' => 'Lax',
      'SESSION_TTL_MINUTES' => '240',
    ];
    self::$cfg = $defaults;

    if (is_readable($envPath)) {
      $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $k = trim(substr($line, 0, $pos));
        $v = trim(substr($line, $pos + 1));
        $v = trim($v, ""'");
        if ($k !== '') self::$cfg[$k] = $v;
      }
    }
  }

  public static function get(string $key): string {
    self::load();
    return (string)(self::$cfg[$key] ?? '');
  }

  public static function bool(string $key): bool {
    $v = strtolower(self::get($key));
    return in_array($v, ['1','true','yes','on'], true);
  }

  public static function int(string $key): int {
    return (int)self::get($key);
  }
}

Config::load();
