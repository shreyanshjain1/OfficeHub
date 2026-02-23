<?php
declare(strict_types=1);

final class Policy {
  public static function canViewRequest(array $user, array $req): bool {
    if ($user['role'] === 'admin') return true;
    if ($user['role'] === 'employee') return (int)$req['requester_user_id'] === (int)$user['id'];
    if ($user['role'] === 'manager') {
      return $user['department_id'] !== null && (int)$req['department_id'] === (int)$user['department_id'];
    }
    return false;
  }

  public static function canEditRequest(array $user, array $req): bool {
    if ($user['role'] === 'admin') return true;
    if ($user['role'] === 'employee' && (int)$req['requester_user_id'] === (int)$user['id']) {
      return in_array($req['status'], ['OPEN','IN_REVIEW'], true);
    }
    return false;
  }

  public static function canApproveRequest(array $user, array $req): bool {
    if ($user['role'] === 'manager') {
      return self::canViewRequest($user, $req) && $req['status'] === 'IN_REVIEW';
    }
    if ($user['role'] === 'admin') {
      return $req['status'] === 'IN_REVIEW';
    }
    return false;
  }

  public static function canAssignRequest(array $user, array $req): bool {
    return $user['role'] === 'admin' && self::canViewRequest($user, $req);
  }

  public static function canComment(array $user, array $req): bool {
    return self::canViewRequest($user, $req);
  }

  public static function canDownloadAttachment(array $user, array $req): bool {
    return self::canViewRequest($user, $req);
  }
}
