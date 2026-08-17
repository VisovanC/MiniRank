<?php

declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../controllers/RegisterController.php';

$controller = new RegisterController($pdo);
$controller->handle();