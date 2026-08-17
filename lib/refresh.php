<?php

declare(strict_types=1);

function simulate_positions(int $days, callable $rand): array
{
    $current = 1 + (int) floor($rand() * 100);
    $positions = [];
    for ($i = 0; $i < $days; $i++) {
        $current += (int) floor($rand() * 5) - 2;
        $current = max(1, min(100, $current));
        $positions[] = $current;
    }
    return $positions;
}

function refresh_today(PDO $pdo, int $projectId, callable $rand): array
{
    $date = date('Y-m-d');
    $count = 0;
    foreach (keyword_list($pdo, $projectId) as $keyword) {
        $previous = position_before($pdo, (int) $keyword['id'], $date);
        if ($previous === null) {
            $next = 1 + (int) floor($rand() * 100);
        } else {
            $next = $previous + (int) floor($rand() * 5) - 2;
            $next = max(1, min(100, $next));
        }
        position_upsert($pdo, (int) $keyword['id'], $date, $next);
        $count++;
    }
    return ['date' => $date, 'count' => $count];
}