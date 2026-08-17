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

$project = project_resolve($pdo, $config, null);
$projectId = (int) $project['id'];

if (keyword_count($pdo, $projectId) === 0) {
    foreach ($demoKeywords as $phrase) {
        keyword_create($pdo, $projectId, $phrase);
    }
}

$days = 30;
$keywords = keyword_list($pdo, $projectId);
$inserted = 0;

foreach ($keywords as $keyword) {
    $inserted += seed_keyword_history($pdo, (int) $keyword['id'], $days);
}

echo sprintf(
    "Seeded %d keywords in project %d (%s), %d new daily positions (last %d days).\n",
    count($keywords),
    $projectId,
    $project['name'],
    $inserted,
    $days
);