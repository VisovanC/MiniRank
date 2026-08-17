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