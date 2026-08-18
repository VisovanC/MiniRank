<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ChartTest extends TestCase
{
    public function testEmptyHistoryReturnsMessageNotSvg(): void
    {
        $this->assertStringStartsWith('<p', chart_svg([]));
    }

    public function testSinglePointRendersSvgWithHighlightedLastPoint(): void
    {
        $svg = chart_svg([['date' => '2026-01-01', 'position' => 5]]);
        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringContainsString('<circle', $svg);
        $this->assertStringContainsString('class="point last"', $svg);
        $this->assertStringContainsString('>5<', $svg);
    }

    public function testRankOneIsDrawnAboveRankHundred(): void
    {
        $svg = chart_svg([
            ['date' => '2026-01-01', 'position' => 1],
            ['date' => '2026-01-02', 'position' => 100],
        ]);
        preg_match('/points="([^"]+)"/', $svg, $matches);
        $points = explode(' ', $matches[1]);
        $yFirst = (float) explode(',', $points[0])[1];
        $yLast = (float) explode(',', $points[1])[1];
        $this->assertLessThan($yLast, $yFirst);
    }

    public function testFlatDataIsPaddedAroundTheValue(): void
    {
        $svg = chart_svg([
            ['date' => '2026-01-01', 'position' => 5],
            ['date' => '2026-01-02', 'position' => 5],
        ]);
        $this->assertStringContainsString('>4<', $svg);
        $this->assertStringContainsString('>6<', $svg);
    }
}