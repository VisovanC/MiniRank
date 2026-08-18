<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EscapeTest extends TestCase
{
    public function testAngleBracketsEscaped(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
    }

    public function testQuotesAndAmpersandEscaped(): void
    {
        $this->assertSame('&quot;&#039;&amp;', e("\"'&"));
    }

    public function testNullBecomesEmptyString(): void
    {
        $this->assertSame('', e(null));
    }

    public function testPlainTextUnchanged(): void
    {
        $this->assertSame('plain text', e('plain text'));
    }
}