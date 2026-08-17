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

$GLOBALS['minirank_asserts'] = 0;
$GLOBALS['minirank_failures'] = 0;
$GLOBALS['minirank_test_file'] = '';

function ok(bool $condition, string $label): void
{
    $GLOBALS['minirank_asserts']++;
    if (!$condition) {
        $GLOBALS['minirank_failures']++;
        echo '  FAIL: ' . $GLOBALS['minirank_test_file'] . ' :: ' . $label . "\n";
    }
}

function eq(mixed $expected, mixed $actual, string $label): void
{
    $passes = $expected === $actual;
    if (!$passes) {
        $label .= ' (expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . ')';
    }
    ok($passes, $label);
}

function memory_pdo(): PDO
{
    $pdo = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

$files = glob(__DIR__ . '/*_test.php');
sort($files);

$start = microtime(true);
$filesOk = 0;
$filesFailed = 0;
foreach ($files as $file) {
    $GLOBALS['minirank_test_file'] = basename($file);
    echo '== ' . basename($file) . "\n";
    try {
        require $file;
        $filesOk++;
        echo '  ok' . "\n";
    } catch (Throwable $e) {
        $filesFailed++;
        $GLOBALS['minirank_failures']++;
        echo '  ERROR: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')' . "\n";
    }
}

echo "\n" . str_repeat('-', 50) . "\n";
echo 'assertions: ' . $GLOBALS['minirank_asserts']
    . ', failures: ' . $GLOBALS['minirank_failures']
    . ', files: ' . $filesOk . '/' . count($files) . " ok\n";
printf('elapsed: %.2fs' . "\n", microtime(true) - $start);
exit($GLOBALS['minirank_failures'] > 0 || $files === [] ? 1 : 0);