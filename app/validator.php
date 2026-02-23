<?php
declare(strict_types=1);

final class Validator {
  public static function str($v, int $min, int $max): ?string {
    if (!is_string($v)) return null;
    $v = trim($v);
    if (mb_strlen($v) < $min || mb_strlen($v) > $max) return null;
    return $v;
  }

  public static function enum($v, array $allowed): ?string {
    if (!is_string($v)) return null;
    return in_array($v, $allowed, true) ? $v : null;
  }

  public static function date($v): ?string {
    if ($v === null || $v === '') return null;
    if (!is_string($v)) return null;
    $d = DateTime::createFromFormat('Y-m-d', $v);
    if (!$d || $d->format('Y-m-d') !== $v) return null;
    return $v;
  }

  public static function int($v, int $min = 1, int $max = PHP_INT_MAX): ?int {
    if ($v === null || $v === '') return null;
    if (!is_numeric($v)) return null;
    $i = (int)$v;
    if ($i < $min || $i > $max) return null;
    return $i;
  }

  public static function email($v): ?string {
    if (!is_string($v)) return null;
    $v = trim(strtolower($v));
    if (strlen($v) < 3 || strlen($v) > 190) return null;
    return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
  }

  public static function rejectUnknownFields(array $input, array $allowedKeys): bool {
    foreach ($input as $k => $_) {
      if (!in_array($k, $allowedKeys, true)) return false;
    }
    return true;
  }
}
