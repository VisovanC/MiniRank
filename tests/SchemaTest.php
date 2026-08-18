<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec("CREATE TABLE projects (id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE, site_url TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT (datetime('now')))");
        $this->pdo->exec('CREATE TABLE keywords (id INTEGER PRIMARY KEY, phrase TEXT NOT NULL UNIQUE, created_at TEXT NOT NULL DEFAULT (datetime(\'now\')))');
        $this->pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT (datetime('now')))");
        $this->pdo->exec('CREATE TABLE positions (id INTEGER PRIMARY KEY, keyword_id INTEGER NOT NULL, date TEXT NOT NULL, position INTEGER NOT NULL CHECK (position BETWEEN 1 AND 100), UNIQUE (keyword_id, date), FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE)');
    }

    private function migrate(): void
    {
        app_schema_init($this->pdo, dirname(__DIR__) . '/schema.sql', [
            'db' => ['path' => ':memory:'],
            'site' => ['url' => 'https://example.test'],
        ]);
    }

    public function testMigrationAddsProjectIdToLegacyKeywords(): void
    {
        $this->migrate();
        $columns = array_column($this->pdo->query('PRAGMA table_info(keywords)')->fetchAll(), 'name');
        $this->assertContains('project_id', $columns);
    }

    public function testMigrationAddsLockoutColumnsToLegacyUsers(): void
    {
        $this->migrate();
        $columns = array_column($this->pdo->query('PRAGMA table_info(users)')->fetchAll(), 'name');
        $this->assertContains('failed_attempts', $columns);
        $this->assertContains('locked_until', $columns);
    }

    public function testMigrationCreatesDefaultProject(): void
    {
        $this->migrate();
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn());
    }

    public function testMigrationIsIdempotent(): void
    {
        $this->migrate();
        $this->migrate();
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn());
    }
}