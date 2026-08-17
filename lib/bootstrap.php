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
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/escape.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: default-src 'self'");

$dataDir = dirname($config['db']['path']);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$pdo = app_pdo($config);
app_schema_init($pdo, __DIR__ . '/../schema.sql', $config);

if (PHP_SAPI !== 'cli') {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('minirank_session');
    session_start();
    $_SESSION['csrf'] ??= csrf_new_token();

    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $posted = (string) ($_POST['csrf'] ?? '');
        $expected = (string) ($_SESSION['csrf'] ?? '');
        if (!csrf_validate($posted, $expected)) {
            http_response_code(403);
            exit('CSRF validation failed.');
        }
    }
}

function current_user_id(): ?int
{
    return empty($_SESSION['user_id']) ? null : (int) $_SESSION['user_id'];
}

function current_username(): string
{
    return (string) ($_SESSION['username'] ?? '');
}

function require_auth(): void
{
    if (current_user_id() === null) {
        header('Location: login.php');
        exit;
    }
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e((string) ($_SESSION['csrf'] ?? '')) . '">';
}

function csrf_meta(): string
{
    return '<meta name="csrf-token" content="' . e((string) ($_SESSION['csrf'] ?? '')) . '">';
}