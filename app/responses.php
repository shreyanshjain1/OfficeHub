<?php
declare(strict_types=1);

final class Response {
  public static function json(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
  }

  public static function redirect(string $to, array $flash = []): void {
    if ($flash) {
      foreach ($flash as $k => $v) {
        $_SESSION['flash'][$k] = (string)$v;
      }
    }
    header('Location: ' . $to);
    exit;
  }

  public static function badRequest(string $message = 'Bad request'): void {
    self::json(['ok' => false, 'error' => $message], 400);
  }

  public static function forbidden(string $message = 'Forbidden'): void {
    self::json(['ok' => false, 'error' => $message], 403);
  }

  public static function notFound(string $message = 'Not found'): void {
    self::json(['ok' => false, 'error' => $message], 404);
  }
}
