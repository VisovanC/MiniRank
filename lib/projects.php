<?php

declare(strict_types=1);

function project_list(PDO $pdo): array
{
    return $pdo->query('SELECT id, name, site_url, created_at FROM projects ORDER BY id ASC')->fetchAll();
}

function project_find(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, name, site_url, created_at FROM projects WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function project_resolve(PDO $pdo, array $config, ?int $requested): array
{
    if ($requested !== null) {
        $project = project_find($pdo, $requested);
        if ($project !== null) {
            return $project;
        }
    }
    return project_find($pdo, 1)
        ?? ['id' => 1, 'name' => 'Default', 'site_url' => $config['site']['url']];
}

function project_create(PDO $pdo, string $name, string $siteUrl): int
{
    $stmt = $pdo->prepare('INSERT INTO projects (name, site_url) VALUES (:name, :site_url)');
    $stmt->execute(['name' => $name, 'site_url' => $siteUrl]);
    return (int) $pdo->lastInsertId();
}