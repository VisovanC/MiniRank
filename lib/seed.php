<?php

declare(strict_types=1);

function seed_keyword_history(PDO $pdo, int $keywordId, int $days = 30, ?callable $rand = null): int
{
    $rand = $rand ?? static fn(): float => mt_rand() / mt_getrandmax();
    $today = new DateTimeImmutable('today');
    $positions = simulate_positions($days, $rand);
    $inserted = 0;
    for ($i = 0; $i < $days; $i++) {
        $date = $today->modify('-' . ($days - 1 - $i) . ' days')->format('Y-m-d');
        $inserted += position_seed($pdo, $keywordId, $date, $positions[$i]);
    }
    return $inserted;
}