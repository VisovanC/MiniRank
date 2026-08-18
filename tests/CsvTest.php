<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CsvTest extends TestCase
{
    public function testSimpleRowUsesCrlf(): void
    {
        $this->assertSame("x,y,z\r\n", csv_encode([['x', 'y', 'z']]));
    }

    public function testQuotingOfCommasQuotesAndNewlines(): void
    {
        $csv = csv_encode([
            ['a', 'b,comma', 'has "quote"', "line\nbreak", null, ''],
        ]);
        $this->assertSame("a,\"b,comma\",\"has \"\"quote\"\"\",\"line\nbreak\",,\r\n", $csv);
    }

    public function testNoRowsProducesEmptyString(): void
    {
        $this->assertSame('', csv_encode([]));
    }
}