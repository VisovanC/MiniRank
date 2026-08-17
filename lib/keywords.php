<?php

declare(strict_types=1);

function keyword_list(PDO $pdo, int $projectId, string $search = ''): array
{
    $sql = 'SELECT id, phrase, created_at FROM keywords WHERE project_id = :project_id';
    $params = ['project_id' => $projectId];
    if ($search !== '') {
        $sql .= " AND phrase LIKE :search ESCAPE '\\'";
        $params['search'] = '%' . keyword_like_escape($search) . '%';
    }
    $sql .= ' ORDER BY phrase COLLATE NOCASE ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function keyword_like_escape(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

function keyword_find(PDO $pdo, int $id, int $projectId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, phrase, created_at FROM keywords WHERE id = :id AND project_id = :project_id'
    );
    $stmt->execute(['id' => $id, 'project_id' => $projectId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function keyword_count(PDO $pdo, int $projectId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM keywords WHERE project_id = :project_id');
    $stmt->execute(['project_id' => $projectId]);
    return (int) $stmt->fetchColumn();
}

function keyword_create(PDO $pdo, int $projectId, string $phrase): int
{
    $stmt = $pdo->prepare('INSERT INTO keywords (phrase, project_id) VALUES (:phrase, :project_id)');
    $stmt->execute(['phrase' => $phrase, 'project_id' => $projectId]);
    return (int) $pdo->lastInsertId();
}

function keyword_update(PDO $pdo, int $id, int $projectId, string $phrase): void
{
    $stmt = $pdo->prepare(
        'UPDATE keywords SET phrase = :phrase WHERE id = :id AND project_id = :project_id'
    );
    $stmt->execute(['phrase' => $phrase, 'id' => $id, 'project_id' => $projectId]);
}

function keyword_delete(PDO $pdo, int $id, int $projectId): void
{
    $stmt = $pdo->prepare('DELETE FROM keywords WHERE id = :id AND project_id = :project_id');
    $stmt->execute(['id' => $id, 'project_id' => $projectId]);
}