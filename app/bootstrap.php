<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/responses.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/rate_limit.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validator.php';
require_once __DIR__ . '/policies.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/helpers.php';

Security::applySecurityHeaders();
Security::startAppSession();