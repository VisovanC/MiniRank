<?php

declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../controllers/KeywordDetailController.php';

$controller = new KeywordDetailController($pdo, $config);
$controller->handle();