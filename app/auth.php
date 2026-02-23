<?php
declare(strict_types=1);

final class Auth {
  private const COOKIE_NAME = 'orh_sid';

  public static function currentUser(): ?array {
    $sid = $_COOKIE[self::COOKIE_NAME] ?? '';
    if (!is_string($sid) || !preg_match('/^[a-f0-9]{64}$/', $sid)) return null;

    $row = DB::fetchOne(
      "SELECT s.id AS sid, s.user_id, s.ip_hash, s.ua_hash, s.csrf_token, s.expires_at, s.revoked_at,
              u.email, u.full_name, u.role, u.department_id, u.is_active
       FROM sessions s
       JOIN users u ON u.id = s.user_id
       WHERE s.id=? LIMIT 1",
      "s",
      [$sid]
    );
    if (!$row) return null;
    if ((int)$row['is_active'] !== 1) return null;
    if ($row['revoked_at']) return null;

    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $exp = new DateTimeImmutable($row['expires_at'], new DateTimeZone('UTC'));
    if ($exp <= $now) return null;

    $ip = Security::getClientIp();
    $ua = Security::getUserAgent();

    if (!hash_equals($row['ip_hash'], Security::ipHash($ip))) return null;
    if (!hash_equals($row['ua_hash'], Security::uaHash($ua))) return null;

    DB::exec("UPDATE sessions SET last_seen_at=NOW() WHERE id=?", "s", [$sid]);

    return [
      'id' => (int)$row['user_id'],
      'email' => $row['email'],
      'full_name' => $row['full_name'],
      'role' => $row['role'],
      'department_id' => $row['department_id'] !== null ? (int)$row['department_id'] : null,
      'csrf_token' => $row['csrf_token'],
      'sid' => $row['sid'],
    ];
  }

  public static function requireLogin(): array {
    $u = self::currentUser();
    if (!$u) Response::redirect('/index.php?page=login', ['toast' => 'Please sign in.']);
    return $u;
  }

  public static function requireRole(array $user, array $roles): void {
    if (!in_array($user['role'], $roles, true)) {
      Audit::log($user['id'], 'AUTHZ_DENY', 'route', null, ['reason' => 'role_required', 'roles' => $roles]);
      http_response_code(403); echo "Forbidden"; exit;
    }
  }

  public static function login(string $email, string $password): array {
    $ip = Security::getClientIp();

    $bucketIp = 'login:ip:' . hash('sha256', $ip);
    $rl1 = RateLimit::checkAndHit($bucketIp, 10, 60);
    if (!$rl1['allowed']) return ['ok' => false, 'error' => 'Invalid email or password'];

    $bucketAcct = 'login:acct:' . hash('sha256', strtolower($email));
    $rl2 = RateLimit::checkAndHit($bucketAcct, 8, 120);
    if (!$rl2['allowed']) return ['ok' => false, 'error' => 'Invalid email or password'];

    $row = DB::fetchOne(
      "SELECT id, email, full_name, role, department_id, password_hash, is_active
       FROM users WHERE email=? LIMIT 1",
      "s",
      [$email]
    );

    $fakeHash = '$2y$12$C6UzMDM.H6dfI/f/IKcEeOqH5o9WfY0Vf2Q8dG6A9LqQvXyQFjv4u';
    $hashToCheck = $row ? $row['password_hash'] : $fakeHash;

    $ok = password_verify($password, $hashToCheck);
    if (!$row || (int)$row['is_active'] !== 1 || !$ok) {
      Audit::log($row ? (int)$row['id'] : null, 'LOGIN_FAIL', 'user', $row ? (int)$row['id'] : null, ['email' => $email, 'ip' => $ip]);
      return ['ok' => false, 'error' => 'Invalid email or password'];
    }

    $sid = Security::randomHex(32);
    $csrf = Security::randomHex(32);
    $ua = Security::getUserAgent();
    $ttl = max(30, Config::int('SESSION_TTL_MINUTES'));

    DB::exec(
      "INSERT INTO sessions (id, user_id, ip_hash, ua_hash, csrf_token, expires_at)
       VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))",
      "sisssi",
      [$sid, (int)$row['id'], Security::ipHash($ip), Security::uaHash($ua), $csrf, $ttl]
    );

    $secure = Config::bool('COOKIE_SECURE') || Security::isHttps();
    setcookie(self::COOKIE_NAME, $sid, [
      'expires' => 0, 'path' => '/', 'domain' => '',
      'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax'
    ]);

    DB::exec("UPDATE users SET last_login_at=NOW(), updated_at=NOW() WHERE id=?", "i", [(int)$row['id']]);
    Audit::log((int)$row['id'], 'LOGIN_OK', 'user', (int)$row['id'], ['ip' => $ip]);

    return ['ok' => true];
  }

  public static function logout(?array $user): void {
    $sid = $_COOKIE[self::COOKIE_NAME] ?? '';
    if (is_string($sid) && preg_match('/^[a-f0-9]{64}$/', $sid)) DB::exec("UPDATE sessions SET revoked_at=NOW() WHERE id=?", "s", [$sid]);

    setcookie(self::COOKIE_NAME, '', [
      'expires' => time() - 3600, 'path' => '/', 'domain' => '',
      'secure' => (Config::bool('COOKIE_SECURE') || Security::isHttps()),
      'httponly' => true, 'samesite' => 'Lax'
    ]);

    if ($user) Audit::log($user['id'], 'LOGOUT', 'session', null, ['sid' => $sid]);
  }

  public static function csrfToken(array $user): string { return $user['csrf_token']; }

  public static function requireCsrf(array $user): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals($user['csrf_token'], $token)) {
      Audit::log($user['id'], 'CSRF_DENY', 'csrf', null, []);
      http_response_code(403); echo "Forbidden"; exit;
    }
  }
}
