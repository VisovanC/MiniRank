<?php

declare(strict_types=1);

function keyword_list(PDO $pdo): array
{
    return $pdo
        ->query('SELECT id, phrase, created_at FROM keywords ORDER BY phrase COLLATE NOCASE ASC')
        ->fetchAll();
}

function keyword_find(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, phrase, created_at FROM keywords WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function keyword_create(PDO $pdo, string $phrase): int
{
    $stmt = $pdo->prepare('INSERT INTO keywords (phrase) VALUES (:phrase)');
    $stmt->execute(['phrase' => $phrase]);
    return (int) $pdo->lastInsertId();
}

function keyword_update(PDO $pdo, int $id, string $phrase): void
{
    $stmt = $pdo->prepare('UPDATE keywords SET phrase = :phrase WHERE id = :id');
    $stmt->execute(['phrase' => $phrase, 'id' => $id]);
}

function keyword_delete(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM keywords WHERE id = :id');
    $stmt->execute(['id' => $id]);
}