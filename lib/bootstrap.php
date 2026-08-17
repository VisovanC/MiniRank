<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/keywords.php';
require_once __DIR__ . '/escape.php';

$dataDir = dirname($config['db']['path']);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$pdo = app_pdo($config);
app_schema_init($pdo, __DIR__ . '/../schema.sql');