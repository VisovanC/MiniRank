<?php

declare(strict_types=1);

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec('CREATE TABLE projects (id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE, site_url TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT (datetime(\'now\')))');
$pdo->exec('CREATE TABLE keywords (id INTEGER PRIMARY KEY, phrase TEXT NOT NULL UNIQUE, created_at TEXT NOT NULL DEFAULT (datetime(\'now\')))');
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT (datetime(\'now\')))');
$pdo->exec('CREATE TABLE positions (id INTEGER PRIMARY KEY, keyword_id INTEGER NOT NULL, date TEXT NOT NULL, position INTEGER NOT NULL CHECK (position BETWEEN 1 AND 100), UNIQUE (keyword_id, date), FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE)');

$config = ['db' => ['path' => ':memory:'], 'site' => ['url' => 'https://example.test']];
app_schema_init($pdo, __DIR__ . '/../schema.sql', $config);

$kwCols = array_column($pdo->query('PRAGMA table_info(keywords)')->fetchAll(), 'name');
ok(in_array('project_id', $kwCols, true), 'project_id added to legacy keywords');

$uCols = array_column($pdo->query('PRAGMA table_info(users)')->fetchAll(), 'name');
ok(in_array('failed_attempts', $uCols, true), 'failed_attempts added to legacy users');
ok(in_array('locked_until', $uCols, true), 'locked_until added to legacy users');

eq(1, (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(), 'Default project created on migration');

app_schema_init($pdo, __DIR__ . '/../schema.sql', $config);
eq(1, (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(), 'migration is idempotent (Default not duplicated)');