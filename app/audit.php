<?php
declare(strict_types=1);

final class Audit {
  public static function log(?int $actorUserId, string $action, string $targetType, ?int $targetId, array $metadata = []): void {
    $ip = Security::getClientIp();
    $ua = Security::getUserAgent();
    $json = $metadata ? json_encode($metadata, JSON_UNESCAPED_SLASHES) : null;

    DB::exec(
      "INSERT INTO audit_logs (actor_user_id, action, target_type, target_id, ip, user_agent, metadata_json)
       VALUES (?, ?, ?, ?, ?, ?, ?)",
      "ississs",
      [$actorUserId, $action, $targetType, $targetId, $ip, $ua, $json]
    );
  }
}
