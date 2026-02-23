<?php
declare(strict_types=1);
$base = $argv[1] ?? null;
if (!$base) { echo "Usage: php tests/run.php http://localhost:8080\n"; exit(1); }
require_once __DIR__ . '/test_bootstrap.php';
require_once __DIR__ . '/test_authz_idor.php';
require_once __DIR__ . '/test_validation.php';
require_once __DIR__ . '/test_upload_policy.php';
$tests = [TestBootstrap::run($base),TestAuthzIdor::run($base),TestValidation::run($base),TestUploadPolicy::run($base)];
$pass=0;$fail=0;
echo "Office Request Hub Tests\nBase: {$base}\n\n";
foreach($tests as $t){$ok=$t['ok']?'PASS':'FAIL';echo "[{$ok}] {$t['name']} - {$t['details']}\n"; if($t['ok'])$pass++; else $fail++;}
echo "\nResult: {$pass} passed, {$fail} failed\n"; exit($fail?1:0);
