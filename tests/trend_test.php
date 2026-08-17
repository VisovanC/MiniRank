<?php

declare(strict_types=1);

eq(null, latest_position([]), 'no history -> null');
eq(
    12,
    latest_position([['date' => '2026-01-01', 'position' => 5], ['date' => '2026-01-02', 'position' => 12]]),
    'latest position from history'
);

eq('improved', trend_from_history([
    ['date' => '2026-01-01', 'position' => 20],
    ['date' => '2026-01-08', 'position' => 12],
]), 'improved when much better');

eq('declined', trend_from_history([
    ['date' => '2026-01-01', 'position' => 12],
    ['date' => '2026-01-08', 'position' => 20],
]), 'declined when much worse');

eq('stable', trend_from_history([
    ['date' => '2026-01-01', 'position' => 10],
    ['date' => '2026-01-08', 'position' => 10],
]), 'stable when roughly equal');

eq(null, trend_from_history([['date' => '2026-01-08', 'position' => 10]]), 'single point -> null');

eq('improved', trend_from_history([
    ['date' => '2026-01-01', 'position' => 20],
    ['date' => '2026-01-02', 'position' => 12],
]), 'prior inside the 7-day window counts');

eq('▲ Improved', trend_label('improved'), 'label improved');
eq('▼ Declined', trend_label('declined'), 'label declined');
eq('─ Stable', trend_label('stable'), 'label stable');
eq('—', trend_label(null), 'label unknown');

eq(
    ['trend' => '', 'pos_from' => null, 'pos_to' => null],
    normalize_filters([]),
    'empty input'
);
eq(
    ['trend' => 'improved', 'pos_from' => null, 'pos_to' => null],
    normalize_filters(['trend' => 'improved']),
    'valid trend kept'
);
eq(
    ['trend' => '', 'pos_from' => null, 'pos_to' => null],
    normalize_filters(['trend' => 'garbage']),
    'invalid trend dropped'
);
eq(
    ['trend' => '', 'pos_from' => 5, 'pos_to' => 20],
    normalize_filters(['pos_from' => '5', 'pos_to' => '20']),
    'positions parsed from strings'
);
eq(
    ['trend' => '', 'pos_from' => 5, 'pos_to' => 20],
    normalize_filters(['pos_from' => '20', 'pos_to' => '5']),
    'inverted range swapped'
);
eq(
    ['trend' => '', 'pos_from' => 1, 'pos_to' => 100],
    normalize_filters(['pos_from' => '-50', 'pos_to' => '500']),
    'positions clamped to 1..100'
);
eq(
    ['trend' => '', 'pos_from' => null, 'pos_to' => null],
    normalize_filters(['pos_from' => 'abc']),
    'non-numeric position ignored'
);

$rows = [
    ['id' => 1, 'position' => 5, 'trend' => 'improved'],
    ['id' => 2, 'position' => 50, 'trend' => 'stable'],
    ['id' => 3, 'position' => null, 'trend' => null],
];
eq(3, count(filter_rows($rows)), 'no filters keeps all rows');
eq([1], array_column(filter_rows($rows, 'improved'), 'id'), 'trend filter');
eq([1, 2], array_column(filter_rows($rows, '', 1, 55), 'id'), 'rank range inclusive, no-position excluded');
eq([2], array_column(filter_rows($rows, 'stable', 40, 60), 'id'), 'trend + rank combined');
eq([], filter_rows($rows, 'declined'), 'no matches');