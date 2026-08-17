<?php

declare(strict_types=1);

function app_dsn(array $config): string
{
    return 'sqlite:' . $config['db']['path'];
}

function app_pdo(array $config): PDO
{
    $pdo = new PDO(app_dsn($config), null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    return $pdo;
}