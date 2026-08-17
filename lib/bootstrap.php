<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/projects.php';
require_once __DIR__ . '/keywords.php';
require_once __DIR__ . '/positions.php';
require_once __DIR__ . '/refresh.php';
require_once __DIR__ . '/trend.php';
require_once __DIR__ . '/chart.php';
require_once __DIR__ . '/seed.php';
require_once __DIR__ . '/escape.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

$dataDir = dirname($config['db']['path']);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$pdo = app_pdo($config);
app_schema_init($pdo, __DIR__ . '/../schema.sql', $config);