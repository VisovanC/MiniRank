<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = memory_pdo();
        app_schema_init($this->pdo, dirname(__DIR__) . '/schema.sql', [
            'db' => ['path' => ':memory:'],
            'site' => ['url' => 'https://example.test'],
        ]);
    }

    public function testDefaultProjectAutoCreated(): void
    {
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn());
    }

    public function testProjectCrud(): void
    {
        $id = project_create($this->pdo, 'Alpha', 'https://alpha.test');
        $this->assertGreaterThan(1, $id);
        $this->assertSame('Alpha', project_find($this->pdo, $id)['name']);
    }

    public function testKeywordCrudIsProjectScoped(): void
    {
        $other = project_create($this->pdo, 'Alpha', 'https://alpha.test');
        $id = keyword_create($this->pdo, 1, 'seo tool');
        $this->assertSame(1, keyword_count($this->pdo, 1));
        $this->assertSame('seo tool', keyword_find($this->pdo, $id, 1)['phrase']);
        $this->assertNull(keyword_find($this->pdo, $id, $other));

        keyword_update($this->pdo, $id, 1, 'seo tools pro');
        $this->assertSame('seo tools pro', keyword_find($this->pdo, $id, 1)['phrase']);

        keyword_delete($this->pdo, $id, 1);
        $this->assertNull(keyword_find($this->pdo, $id, 1));
    }

    public function testSearchTreatsPercentLiterally(): void
    {
        keyword_create($this->pdo, 1, '100% match');
        $this->assertCount(1, keyword_list($this->pdo, 1, '100%'));
    }

    public function testLikeEscape(): void
    {
        $this->assertSame('a\%b\_c\\\\d', keyword_like_escape('a%b_c\d'));
    }

    public function testPositionSeedIsIdempotent(): void
    {
        $id = keyword_create($this->pdo, 1, 'coffee');
        position_seed($this->pdo, $id, '2026-01-01', 10);
        position_seed($this->pdo, $id, '2026-01-01', 20);
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM positions')->fetchColumn());
    }

    public function testPositionUpsertUpdatesInPlace(): void
    {
        $id = keyword_create($this->pdo, 1, 'coffee');
        position_upsert($this->pdo, $id, '2026-01-01', 30);
        position_upsert($this->pdo, $id, '2026-01-02', 28);
        $this->assertSame(
            30,
            (int) $this->pdo->query('SELECT position FROM positions WHERE keyword_id = ' . $id . ' AND date = \'2026-01-01\'')->fetchColumn()
        );
        $this->assertSame(28, position_before($this->pdo, $id, '2026-01-03'));
        $this->assertSame('2026-01-02', position_last_date($this->pdo, 1));
        $this->assertSame('2026-01-02', positions_for_keyword($this->pdo, $id)[0]['date']);
    }
}