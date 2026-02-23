<?php
declare(strict_types=1);

final class Http {
  public static function postForm(string $base, string $path, array $fields, array &$cookies): array {
    $url = rtrim($base, '/') . $path;
    $cookieHeader = self::cookieHeader($cookies);
    $opts = ['http' => [
      'method' => 'POST',
      'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                  ($cookieHeader ? "Cookie: {$cookieHeader}\r\n" : "") .
                  "Connection: close\r\n",
      'content' => http_build_query($fields),
      'ignore_errors' => true,
    ]];
    $ctx = stream_context_create($opts);
    $body = file_get_contents($url, false, $ctx);
    $headers = $http_response_header ?? [];
    self::captureCookies($headers, $cookies);
    return ['headers'=>$headers,'body'=>$body ?: '','status'=>self::status($headers)];
  }

  public static function get(string $base, string $path, array &$cookies): array {
    $url = rtrim($base, '/') . $path;
    $cookieHeader = self::cookieHeader($cookies);
    $opts = ['http' => [
      'method' => 'GET',
      'header' => ($cookieHeader ? "Cookie: {$cookieHeader}\r\n" : "") . "Connection: close\r\n",
      'ignore_errors' => true,
    ]];
    $ctx = stream_context_create($opts);
    $body = file_get_contents($url, false, $ctx);
    $headers = $http_response_header ?? [];
    self::captureCookies($headers, $cookies);
    return ['headers'=>$headers,'body'=>$body ?: '','status'=>self::status($headers)];
  }

  private static function status(array $headers): int {
    foreach ($headers as $h) if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $h, $m)) return (int)$m[1];
    return 0;
  }

  private static function captureCookies(array $headers, array &$cookies): void {
    foreach ($headers as $h) {
      if (stripos($h, 'Set-Cookie:') === 0) {
        $cookieLine = trim(substr($h, strlen('Set-Cookie:')));
        $parts = explode(';', $cookieLine);
        $kv = explode('=', trim($parts[0]), 2);
        if (count($kv) === 2) $cookies[$kv[0]] = $kv[1];
      }
    }
  }

  private static function cookieHeader(array $cookies): string {
    return implode('; ', array_map(fn($k,$v)=>$k.'='.$v, array_keys($cookies), $cookies));
  }
}
