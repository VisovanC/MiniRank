<?php

declare(strict_types=1);

$base = dirname(__DIR__);
require_once $base . '/lib/db.php';
require_once $base . '/lib/schema.php';
require_once $base . '/lib/projects.php';
require_once $base . '/lib/keywords.php';
require_once $base . '/lib/positions.php';
require_once $base . '/lib/refresh.php';
require_once $base . '/lib/trend.php';
require_once $base . '/lib/chart.php';
require_once $base . '/lib/csv.php';
require_once $base . '/lib/seed.php';
require_once $base . '/lib/users.php';
require_once $base . '/lib/csrf.php';
require_once $base . '/lib/escape.php';

function memory_pdo(): PDO
{
    $pdo = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}