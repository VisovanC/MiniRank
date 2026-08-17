<?php

declare(strict_types=1);

function chart_svg(array $history): string
{
    $count = count($history);
    if ($count === 0) {
        return '<p class="muted">No position history to chart yet.</p>';
    }

    $width = 600;
    $height = 300;
    $padLeft = 42;
    $padRight = 16;
    $padTop = 12;
    $padBottom = 28;
    $plotW = $width - $padLeft - $padRight;
    $plotH = $height - $padTop - $padBottom;

    $positions = array_map(static fn (array $row): int => (int) $row['position'], $history);
    $min = min($positions);
    $max = max($positions);
    if ($min === $max) {
        $min = max(1, $min - 1);
        $max = min(100, $max + 1);
    }

    $x = static fn (int $i): float => $padLeft + ($count === 1 ? 0 : $i * $plotW / ($count - 1));
    $y = static fn (int $p): float => $padTop + (($p - $min) / ($max - $min)) * $plotH;

    $svg = '<svg class="chart" viewBox="0 0 ' . $width . ' ' . $height
        . '" role="img" aria-label="Position history chart" xmlns="http://www.w3.org/2000/svg">';

    foreach ([$max, (int) (($max + $min) / 2), $min] as $value) {
        $yy = $y($value);
        $svg .= sprintf(
            '<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" class="grid"/>',
            $padLeft,
            $yy,
            $width - $padRight,
            $yy
        );
        $svg .= sprintf('<text x="%d" y="%.1f" class="axis">%d</text>', $padLeft - 6, $yy + 4, $value);
    }

    foreach ([0, intdiv($count - 1, 2), $count - 1] as $i) {
        $svg .= sprintf(
            '<text x="%.1f" y="%d" class="axis mid">%s</text>',
            $x($i),
            $height - 8,
            e($history[$i]['date'])
        );
    }

    $points = [];
    foreach ($history as $i => $row) {
        $points[] = sprintf('%.1f,%.1f', $x($i), $y((int) $row['position']));
    }
    $svg .= '<polyline class="line" points="' . implode(' ', $points) . '"/>';

    foreach ($history as $i => $row) {
        $isLast = $i === $count - 1;
        $svg .= sprintf(
            '<circle class="point%s" cx="%.1f" cy="%.1f" r="%d">'
            . '<title>%s: %s</title></circle>',
            $isLast ? ' last' : '',
            $x($i),
            $y((int) $row['position']),
            $isLast ? 4.5 : 3,
            e($row['date']),
            (int) $row['position']
        );
    }

    return $svg . '</svg>';
}