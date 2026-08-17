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

function trend_label(?string $trend): string
{
    return match ($trend) {
        'improved' => '▲ Improved',
        'declined' => '▼ Declined',
        'stable' => '─ Stable',
        default => '—',
    };
}

function keyword_rows_with_metrics(PDO $pdo, int $projectId, string $search = ''): array
{
    $keywords = keyword_list($pdo, $projectId, $search);
    $histories = all_keyword_histories($pdo, $projectId);
    $rows = [];
    foreach ($keywords as $k) {
        $history = $histories[(int) $k['id']] ?? [];
        $rows[] = [
            'id' => (int) $k['id'],
            'phrase' => $k['phrase'],
            'created_at' => $k['created_at'],
            'position' => latest_position($history),
            'trend' => trend_from_history($history),
        ];
    }
    return $rows;
}

function filter_rows(array $rows, string $trend = '', ?int $posFrom = null, ?int $posTo = null): array
{
    return array_values(array_filter($rows, static function (array $row) use ($trend, $posFrom, $posTo): bool {
        if ($trend !== '' && ($row['trend'] ?? null) !== $trend) {
            return false;
        }
        if ($posFrom !== null || $posTo !== null) {
            if ($row['position'] === null) {
                return false;
            }
            if ($posFrom !== null && (int) $row['position'] < $posFrom) {
                return false;
            }
            if ($posTo !== null && (int) $row['position'] > $posTo) {
                return false;
            }
        }
        return true;
    }));
}