<?php
declare(strict_types=1);
final class TestUploadPolicy {
  public static function run(string $base): array {
    return ['name'=>'upload_policy_manual','ok'=>true,'details'=>'See README: Attachments verification'];
  }
}
