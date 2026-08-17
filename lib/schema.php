<?php

declare(strict_types=1);

function app_schema_init(PDO $pdo, string $schemaFile, array $config): void
{
    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        throw new RuntimeException('Schema file not found: ' . $schemaFile);
    }
    $pdo->exec($sql);

    $hasProjectId = false;
    foreach ($pdo->query('PRAGMA table_info(keywords)')->fetchAll() as $column) {
        if ($column['name'] === 'project_id') {
            $hasProjectId = true;
            break;
        }
    }
    if (!$hasProjectId) {
        $pdo->exec('ALTER TABLE keywords ADD COLUMN project_id INTEGER NOT NULL DEFAULT 1');
    }
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_keywords_project ON keywords (project_id)');

    $exists = $pdo->query("SELECT COUNT(*) FROM projects WHERE name = 'Default'")->fetchColumn();
    if ((int) $exists === 0) {
        $stmt = $pdo->prepare('INSERT INTO projects (name, site_url) VALUES (:name, :site_url)');
        $stmt->execute(['name' => 'Default', 'site_url' => $config['site']['url']]);
    }

    $userColumns = array_column($pdo->query('PRAGMA table_info(users)')->fetchAll(), 'name');
    if (!in_array('failed_attempts', $userColumns, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN failed_attempts INTEGER NOT NULL DEFAULT 0');
    }
    if (!in_array('locked_until', $userColumns, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN locked_until TEXT');
    }
}