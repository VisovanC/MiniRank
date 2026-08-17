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