<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SeedTest extends TestCase
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

    public function testSeedKeywordHistoryInsertsAllDays(): void
    {
        $id = keyword_create($this->pdo, 1, 'seeded');
        $inserted = seed_keyword_history($this->pdo, $id, 30, static fn(): float => 0.5);
        $this->assertSame(30, $inserted);
        $this->assertCount(30, positions_for_keyword($this->pdo, $id));
    }

    public function testSeedKeywordHistoryIsIdempotent(): void
    {
        $id = keyword_create($this->pdo, 1, 'seeded');
        seed_keyword_history($this->pdo, $id, 30, static fn(): float => 0.5);
        $again = seed_keyword_history($this->pdo, $id, 30, static fn(): float => 0.5);
        $this->assertSame(0, $again);
        $this->assertCount(30, positions_for_keyword($this->pdo, $id));
    }

    public function testSeedKeywordHistoryEndsTodayAndIsConsecutive(): void
    {
        $id = keyword_create($this->pdo, 1, 'seeded');
        seed_keyword_history($this->pdo, $id, 30, static fn(): float => 0.5);
        $dates = array_column(positions_for_keyword($this->pdo, $id), 'date');
        $this->assertSame(date('Y-m-d'), $dates[0]);
        $this->assertSame(date('Y-m-d', strtotime('-29 days')), $dates[29]);
        $this->assertSame(30, count(array_unique($dates)));
    }

    public function testSimulatePositionsIsDeterministicAndClamped(): void
    {
        $this->assertSame([1, 1, 1, 1, 1, 1, 1, 1, 1, 1], simulate_positions(10, static fn(): float => 0.0));
    }

    public function testSimulatePositionsStaysInRange(): void
    {
        $positions = simulate_positions(500, static fn(): float => 0.999);
        foreach ($positions as $position) {
            $this->assertGreaterThanOrEqual(1, $position);
            $this->assertLessThanOrEqual(100, $position);
        }
    }

    public function testRefreshTodayUpsertsForEveryProjectKeyword(): void
    {
        keyword_create($this->pdo, 1, 'a');
        keyword_create($this->pdo, 1, 'b');
        $result = refresh_today($this->pdo, 1, static fn(): float => 0.5);
        $this->assertSame(date('Y-m-d'), $result['date']);
        $this->assertSame(2, $result['count']);
        $this->assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM positions')->fetchColumn());
    }
}