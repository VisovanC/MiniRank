<?php

declare(strict_types=1);

$pdo = memory_pdo();
$config = ['db' => ['path' => ':memory:'], 'site' => ['url' => 'https://example.test']];
app_schema_init($pdo, __DIR__ . '/../schema.sql', $config);

eq(1, (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(), 'Default project auto-created');

$pid = project_create($pdo, 'Alpha', 'https://alpha.test');
ok($pid > 1, 'project_create returns a new id');
eq('Alpha', project_find($pdo, $pid)['name'], 'project_find by id');

$id1 = keyword_create($pdo, 1, 'seo tool');
$id2 = keyword_create($pdo, 1, 'coffee');
eq(2, keyword_count($pdo, 1), 'keyword_count counts project keywords');
eq('seo tool', keyword_find($pdo, $id1, 1)['phrase'], 'keyword_find within project');
eq(null, keyword_find($pdo, $id1, $pid), 'cross-project find blocked');
keyword_update($pdo, $id1, 1, 'seo tools pro');
eq('seo tools pro', keyword_find($pdo, $id1, 1)['phrase'], 'keyword_update');
keyword_delete($pdo, $id1, 1);
eq(null, keyword_find($pdo, $id1, 1), 'keyword_delete');

keyword_create($pdo, 1, '100% match');
eq(1, count(keyword_list($pdo, 1, '100%')), 'search treats % literally');
eq('a\%b\_c\\\\d', keyword_like_escape('a%b_c\d'), 'like escape escapes %, _ and \\');

position_seed($pdo, $id2, '2026-01-01', 10);
position_seed($pdo, $id2, '2026-01-01', 20);
eq(1, (int) $pdo->query('SELECT COUNT(*) FROM positions')->fetchColumn(), 'position_seed is idempotent');
position_upsert($pdo, $id2, '2026-01-01', 30);
eq(30, (int) $pdo->query('SELECT position FROM positions WHERE keyword_id = ' . $id2 . ' AND date = \'2026-01-01\'')->fetchColumn(), 'position_upsert updates in place');
position_upsert($pdo, $id2, '2026-01-02', 28);
eq(28, position_before($pdo, $id2, '2026-01-03'), 'position_before returns prior rank');
eq('2026-01-02', position_last_date($pdo, 1), 'position_last_date for project');
eq('2026-01-02', positions_for_keyword($pdo, $id2)[0]['date'], 'history is newest first');

eq([1, 1, 1, 1, 1, 1, 1, 1, 1, 1], simulate_positions(10, static fn(): float => 0.0), 'simulate_positions deterministic and clamped');

$res = refresh_today($pdo, 1, static fn(): float => 0.5);
eq(date('Y-m-d'), $res['date'], 'refresh date is today');
eq((int) keyword_count($pdo, 1), $res['count'], 'refresh touches every project keyword');

$seedId = keyword_create($pdo, 1, 'seeded');
eq(30, seed_keyword_history($pdo, $seedId, 30, static fn(): float => 0.5), 'seed_keyword_history inserts all days');
eq(30, count(positions_for_keyword($pdo, $seedId)), 'seeded keyword has 30 rows');