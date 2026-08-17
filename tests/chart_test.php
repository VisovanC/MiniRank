<?php

declare(strict_types=1);

ok(str_starts_with(chart_svg([]), '<p'), 'empty history returns a message, not svg');

$single = chart_svg([['date' => '2026-01-01', 'position' => 5]]);
ok(str_starts_with($single, '<svg'), 'single point renders svg');
ok(str_contains($single, '<circle'), 'single point has a circle');
ok(str_contains($single, 'class="point last"'), 'last point is highlighted');
ok(str_contains($single, '>5<'), 'single point axis labels show the value');

$two = chart_svg([
    ['date' => '2026-01-01', 'position' => 1],
    ['date' => '2026-01-02', 'position' => 100],
]);
preg_match('/points="([^"]+)"/', $two, $m);
$points = explode(' ', $m[1]);
$y1 = (float) explode(',', $points[0])[1];
$y2 = (float) explode(',', $points[1])[1];
ok($y1 < $y2, 'rank 1 is drawn above rank 100 (smaller y)');

$flat = chart_svg([
    ['date' => '2026-01-01', 'position' => 5],
    ['date' => '2026-01-02', 'position' => 5],
]);
ok(str_contains($flat, '>4<') && str_contains($flat, '>6<'), 'flat data is padded around the value');