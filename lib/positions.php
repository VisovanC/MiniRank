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

function position_last_date(PDO $pdo, int $projectId): ?string
{
    $stmt = $pdo->prepare(
        'SELECT MAX(p.date) FROM positions p
         JOIN keywords k ON k.id = p.keyword_id
         WHERE k.project_id = :project_id'
    );
    $stmt->execute(['project_id' => $projectId]);
    $value = $stmt->fetchColumn();
    return $value === false || $value === null ? null : (string) $value;
}

function all_keyword_histories(PDO $pdo, int $projectId): array
{
    $stmt = $pdo->prepare(
        'SELECT p.keyword_id, p.date, p.position
         FROM positions p
         JOIN keywords k ON k.id = p.keyword_id
         WHERE k.project_id = :project_id
         ORDER BY p.keyword_id, p.date ASC, p.id ASC'
    );
    $stmt->execute(['project_id' => $projectId]);
    $rows = $stmt->fetchAll();
    $histories = [];
    foreach ($rows as $row) {
        $histories[(int) $row['keyword_id']][] = [
            'date' => $row['date'],
            'position' => (int) $row['position'],
        ];
    }
    return $histories;
}

function positions_for_keyword(PDO $pdo, int $keywordId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, date, position FROM positions WHERE keyword_id = :keyword_id ORDER BY date DESC, id DESC'
    );
    $stmt->execute(['keyword_id' => $keywordId]);
    return $stmt->fetchAll();
}