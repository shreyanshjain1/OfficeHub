<?php
declare(strict_types=1);
require_once __DIR__ . '/util_http.php';
final class TestBootstrap {
  public static function run(string $base): array {
    $cookies = [];
    $r = Http::get($base, '/health.php', $cookies);
    $ok = ($r['status'] === 200 && str_contains($r['body'], 'OK'));
    return ['name'=>'healthcheck','ok'=>$ok,'details'=>'status='.$r['status']];
  }
}
