<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TrendTest extends TestCase
{
    public function testLatestPositionReturnsNullForEmptyHistory(): void
    {
        $this->assertNull(latest_position([]));
    }

    public function testLatestPositionTakesLastRow(): void
    {
        $history = [
            ['date' => '2026-01-01', 'position' => 5],
            ['date' => '2026-01-02', 'position' => 12],
        ];
        $this->assertSame(12, latest_position($history));
    }

    public function testTrendImproved(): void
    {
        $history = [
            ['date' => '2026-01-01', 'position' => 20],
            ['date' => '2026-01-08', 'position' => 12],
        ];
        $this->assertSame('improved', trend_from_history($history));
    }

    public function testTrendDeclined(): void
    {
        $history = [
            ['date' => '2026-01-01', 'position' => 12],
            ['date' => '2026-01-08', 'position' => 20],
        ];
        $this->assertSame('declined', trend_from_history($history));
    }

    public function testTrendStable(): void
    {
        $history = [
            ['date' => '2026-01-01', 'position' => 10],
            ['date' => '2026-01-08', 'position' => 10],
        ];
        $this->assertSame('stable', trend_from_history($history));
    }

    public function testTrendIsNullForSinglePoint(): void
    {
        $this->assertNull(trend_from_history([['date' => '2026-01-08', 'position' => 10]]));
    }

    public function testTrendUsesPriorPointInsideSevenDayWindow(): void
    {
        $history = [
            ['date' => '2026-01-01', 'position' => 20],
            ['date' => '2026-01-02', 'position' => 12],
        ];
        $this->assertSame('improved', trend_from_history($history));
    }

    public function testTrendLabels(): void
    {
        $this->assertSame('▲ Improved', trend_label('improved'));
        $this->assertSame('▼ Declined', trend_label('declined'));
        $this->assertSame('─ Stable', trend_label('stable'));
        $this->assertSame('—', trend_label(null));
    }

    public function testNormalizeFiltersEmptyInput(): void
    {
        $this->assertSame(['trend' => '', 'pos_from' => null, 'pos_to' => null], normalize_filters([]));
    }

    public function testNormalizeFiltersKeepsValidTrend(): void
    {
        $this->assertSame(
            ['trend' => 'improved', 'pos_from' => null, 'pos_to' => null],
            normalize_filters(['trend' => 'improved'])
        );
    }

    public function testNormalizeFiltersDropsInvalidTrend(): void
    {
        $this->assertSame(
            ['trend' => '', 'pos_from' => null, 'pos_to' => null],
            normalize_filters(['trend' => 'garbage'])
        );
    }

    public function testNormalizeFiltersParsesPositionsFromStrings(): void
    {
        $this->assertSame(
            ['trend' => '', 'pos_from' => 5, 'pos_to' => 20],
            normalize_filters(['pos_from' => '5', 'pos_to' => '20'])
        );
    }

    public function testNormalizeFiltersSwapsInvertedRange(): void
    {
        $this->assertSame(
            ['trend' => '', 'pos_from' => 5, 'pos_to' => 20],
            normalize_filters(['pos_from' => '20', 'pos_to' => '5'])
        );
    }

    public function testNormalizeFiltersClampsPositionsToOneToHundred(): void
    {
        $this->assertSame(
            ['trend' => '', 'pos_from' => 1, 'pos_to' => 100],
            normalize_filters(['pos_from' => '-50', 'pos_to' => '500'])
        );
    }

    public function testNormalizeFiltersIgnoresNonNumericPosition(): void
    {
        $this->assertSame(
            ['trend' => '', 'pos_from' => null, 'pos_to' => null],
            normalize_filters(['pos_from' => 'abc'])
        );
    }

    public function testFilterRowsKeepsEverythingWithoutFilters(): void
    {
        $this->assertCount(3, filter_rows($this->sampleRows()));
    }

    public function testFilterRowsByTrend(): void
    {
        $this->assertSame([1], array_column(filter_rows($this->sampleRows(), 'improved'), 'id'));
    }

    public function testFilterRowsByRankRangeIsInclusiveAndExcludesNoPosition(): void
    {
        $this->assertSame([1, 2], array_column(filter_rows($this->sampleRows(), '', 1, 55), 'id'));
    }

    public function testFilterRowsCombinesTrendAndRank(): void
    {
        $this->assertSame([2], array_column(filter_rows($this->sampleRows(), 'stable', 40, 60), 'id'));
    }

    public function testFilterRowsReturnsEmptyWhenNothingMatches(): void
    {
        $this->assertSame([], filter_rows($this->sampleRows(), 'declined'));
    }

    private function sampleRows(): array
    {
        return [
            ['id' => 1, 'position' => 5, 'trend' => 'improved'],
            ['id' => 2, 'position' => 50, 'trend' => 'stable'],
            ['id' => 3, 'position' => null, 'trend' => null],
        ];
    }
}