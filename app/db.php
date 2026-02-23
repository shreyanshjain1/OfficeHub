<?php
declare(strict_types=1);

final class DB {
  private static ?mysqli $db = null;

  public static function conn(): mysqli {
    if (self::$db) return self::$db;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli(
      Config::get('DB_HOST'),
      Config::get('DB_USER'),
      Config::get('DB_PASS'),
      Config::get('DB_NAME'),
      (int)Config::get('DB_PORT')
    );
    $db->set_charset('utf8mb4');

    self::$db = $db;
    return self::$db;
  }

  public static function fetchOne(string $sql, string $types = '', array $params = []): ?array {
    $stmt = self::conn()->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    return $row ?: null;
  }

  public static function fetchAll(string $sql, string $types = '', array $params = []): array {
    $stmt = self::conn()->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
  }

  public static function exec(string $sql, string $types = '', array $params = []): int {
    $stmt = self::conn()->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->affected_rows;
  }

  public static function insertId(): int {
    return (int)self::conn()->insert_id;
  }
}
