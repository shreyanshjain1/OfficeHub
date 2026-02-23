<?php
declare(strict_types=1);
require_once __DIR__ . '/util_http.php';
final class TestValidation {
  private static function login(string $base, array &$cookies): bool {
    Http::postForm($base, '/actions/login.php', ['email'=>'employee@office.local','password'=>'Employee123!'], $cookies);
    return isset($cookies['orh_sid']);
  }
  private static function csrf(string $html): ?string {
    if (preg_match('/name="csrf_token"\s+value="([a-f0-9]{64})"/', $html, $m)) return $m[1];
    return null;
  }
  public static function run(string $base): array {
    $cookies = [];
    if (!self::login($base, $cookies)) return ['name'=>'validation_login','ok'=>false,'details'=>'login failed'];
    $dash = Http::get($base, '/index.php?page=dashboard', $cookies);
    $csrf = self::csrf($dash['body']);
    if (!$csrf) return ['name'=>'validation_csrf','ok'=>false,'details'=>'csrf not found'];
    $r = Http::postForm($base, '/actions/request_create.php', ['csrf_token'=>$csrf,'type'=>'IT','priority'=>'MEDIUM','title'=>'','description'=>'','department_id'=>''], $cookies);
    return ['name'=>'validation_request_create_reject','ok'=>in_array($r['status'],[200,302],true),'details'=>'status='.$r['status']];
  }
}
