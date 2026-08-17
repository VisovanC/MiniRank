<?php

declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

$demoKeywords = [
    'best seo tool',
    'keyword rank tracker',
    'google ranking checker',
    'seo competitor analysis',
    'backlink checker free',
    'website position tracker',
    'local seo services',
    'meta title checker',
];

if (keyword_count($pdo) === 0) {
    foreach ($demoKeywords as $phrase) {
        keyword_create($pdo, $phrase);
    }
}

$days = 30;
$today = new DateTimeImmutable('today');
$keywords = keyword_list($pdo);
$inserted = 0;

foreach ($keywords as $keyword) {
    $positions = simulate_positions($days, static fn(): float => mt_rand() / mt_getrandmax());
    for ($i = 0; $i < $days; $i++) {
        $date = $today->modify('-' . ($days - 1 - $i) . ' days')->format('Y-m-d');
        $inserted += position_seed($pdo, (int) $keyword['id'], $date, $positions[$i]);
    }
}

echo sprintf(
    "Seeded %d keywords, %d new daily positions (last %d days).\n",
    count($keywords),
    $inserted,
    $days
);