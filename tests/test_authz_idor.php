<?php
declare(strict_types=1);
require_once __DIR__ . '/util_http.php';
final class TestAuthzIdor {
  private static function login(string $base, string $email, string $pass, array &$cookies): bool {
    $r = Http::postForm($base, '/actions/login.php', ['email'=>$email,'password'=>$pass], $cookies);
    return in_array($r['status'], [200,302], true) && isset($cookies['orh_sid']);
  }
  public static function run(string $base): array {
    $cookies = [];
    if (!self::login($base, 'employee@office.local', 'Employee123!', $cookies)) return ['name'=>'idor_login_employee','ok'=>false,'details'=>'login failed'];
    $r1 = Http::get($base, '/index.php?page=request_view&id=999999', $cookies);
    $ok1 = in_array($r1['status'], [403,404], true);
    $r2 = Http::get($base, '/index.php?page=request_view&id=2', $cookies);
    $ok2 = in_array($r2['status'], [200,403,404], true);
    return ['name'=>'idor_employee_access','ok'=>($ok1 && $ok2),'details'=>"r1={$r1['status']} r2={$r2['status']}"];
  }
}
