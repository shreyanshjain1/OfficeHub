<?php
declare(strict_types=1);

final class Security {
  public static function applySecurityHeaders(): void {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');

    $https = self::isHttps();
    if ($https) {
      header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    $csp = [
      "default-src 'self'",
      "base-uri 'self'",
      "form-action 'self'",
      "frame-ancestors 'none'",
      "object-src 'none'",
      "script-src 'self' https://cdn.jsdelivr.net",
      "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com",
      "font-src 'self' https://fonts.gstatic.com",
      "img-src 'self' data:",
      "connect-src 'self'",
      "upgrade-insecure-requests"
    ];
    header('Content-Security-Policy: ' . implode('; ', $csp));
  }

  public static function isHttps(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
    return false;
  }

  public static function startAppSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $secure = Config::bool('COOKIE_SECURE') || self::isHttps();
    session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/',
      'domain' => '',
      'secure' => $secure,
      'httponly' => true,
      'samesite' => Config::get('COOKIE_SAMESITE') ?: 'Lax'
    ]);
    session_start();
    if (!isset($_SESSION['flash'])) $_SESSION['flash'] = [];
  }

  public static function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }

  public static function randomHex(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
  }

  public static function ipHash(string $ip): string {
    return hash('sha256', $ip . '|' . Config::get('APP_SECRET'));
  }

  public static function uaHash(string $ua): string {
    return hash('sha256', $ua . '|' . Config::get('APP_SECRET'));
  }

  public static function requirePost(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
      http_response_code(405);
      echo "Method Not Allowed";
      exit;
    }
  }

  public static function getClientIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  }

  public static function getUserAgent(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return substr($ua, 0, 255);
  }

  public static function getTheme(): string {
    $t = $_COOKIE['theme'] ?? 'light';
    return in_array($t, ['light','dark'], true) ? $t : 'light';
  }

  public static function setThemeCookie(string $theme): void {
    $secure = Config::bool('COOKIE_SECURE') || self::isHttps();
    setcookie('theme', $theme, [
      'expires' => time() + 60*60*24*365,
      'path' => '/',
      'domain' => '',
      'secure' => $secure,
      'httponly' => true,
      'samesite' => 'Lax'
    ]);
  }
}
