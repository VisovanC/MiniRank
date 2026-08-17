<?php

declare(strict_types=1);

function latest_position(array $history): ?int
{
    $last = end($history);
    return $last === false ? null : (int) $last['position'];
}

function trend_from_history(array $history): ?string
{
    $count = count($history);
    if ($count < 2) {
        return null;
    }

    $latest = (int) $history[$count - 1]['position'];
    $latestDate = new DateTimeImmutable($history[$count - 1]['date']);
    $cutoff = $latestDate->modify('-7 days');

    $prior = null;
    foreach ($history as $row) {
        $date = new DateTimeImmutable($row['date']);
        if ($date >= $cutoff && $date < $latestDate) {
            $prior = (int) $row['position'];
            break;
        }
    }
    if ($prior === null) {
        return null;
    }

    $diff = $prior - $latest;
    if ($diff > 1) {
        return 'improved';
    }
    if ($diff < -1) {
        return 'declined';
    }
    return 'stable';
}