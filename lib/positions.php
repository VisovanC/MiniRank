<?php

declare(strict_types=1);

function position_seed(PDO $pdo, int $keywordId, string $date, int $position): int
{
    $stmt = $pdo->prepare(
        'INSERT OR IGNORE INTO positions (keyword_id, date, position) VALUES (:keyword_id, :date, :position)'
    );
    $stmt->execute(['keyword_id' => $keywordId, 'date' => $date, 'position' => $position]);
    return $stmt->rowCount();
}

function position_upsert(PDO $pdo, int $keywordId, string $date, int $position): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO positions (keyword_id, date, position) VALUES (:keyword_id, :date, :position)
         ON CONFLICT(keyword_id, date) DO UPDATE SET position = excluded.position'
    );
    $stmt->execute(['keyword_id' => $keywordId, 'date' => $date, 'position' => $position]);
}

function position_before(PDO $pdo, int $keywordId, string $date): ?int
{
    $stmt = $pdo->prepare(
        'SELECT position FROM positions WHERE keyword_id = :keyword_id AND date < :date ORDER BY date DESC LIMIT 1'
    );
    $stmt->execute(['keyword_id' => $keywordId, 'date' => $date]);
    $value = $stmt->fetchColumn();
    return $value === false ? null : (int) $value;
}

function position_last_date(PDO $pdo): ?string
{
    $value = $pdo->query('SELECT MAX(date) FROM positions')->fetchColumn();
    return $value === false || $value === null ? null : (string) $value;
}