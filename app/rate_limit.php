<?php
declare(strict_types=1);

final class RateLimit {
  public static function checkAndHit(string $bucketKey, int $maxPerWindow, int $lockSecondsAfterMax): array {
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $windowStart = $now->format('Y-m-d H:i:00');
    $row = DB::fetchOne("SELECT id, attempts, locked_until, window_start FROM rate_limits WHERE bucket_key=? LIMIT 1", "s", [$bucketKey]);

    if ($row) {
      $lockedUntil = $row['locked_until'] ? new DateTimeImmutable($row['locked_until'], new DateTimeZone('UTC')) : null;
      if ($lockedUntil && $lockedUntil > $now) {
        return ['allowed' => false, 'locked_until' => $lockedUntil->format(DateTimeInterface::ATOM)];
      }

      $sameWindow = ($row['window_start'] === $windowStart);
      $attempts = (int)$row['attempts'];
      if (!$sameWindow) $attempts = 0;

      $attempts++;
      $lockedUntilStr = null;
      $allowed = true;

      if ($attempts > $maxPerWindow) {
        $allowed = false;
        $lockedUntilStr = $now->modify("+{$lockSecondsAfterMax} seconds")->format('Y-m-d H:i:s');
      }

      DB::exec(
        "UPDATE rate_limits SET window_start=?, attempts=?, locked_until=? WHERE id=?",
        "sisi",
        [$windowStart, $attempts, $lockedUntilStr, (int)$row['id']]
      );

      return ['allowed' => $allowed, 'locked_until' => $lockedUntilStr];
    }

    DB::exec(
      "INSERT INTO rate_limits (bucket_key, window_start, attempts, locked_until) VALUES (?, ?, 1, NULL)",
      "ss",
      [$bucketKey, $windowStart]
    );

    return ['allowed' => true, 'locked_until' => null];
  }
}
