<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    public function testTokenIsSixtyFourHexCharacters(): void
    {
        $this->assertSame(64, strlen(csrf_new_token()));
    }

    public function testValidTokenPasses(): void
    {
        $token = csrf_new_token();
        $this->assertTrue(csrf_validate($token, $token));
    }

    public function testTamperedTokenFails(): void
    {
        $token = csrf_new_token();
        $this->assertFalse(csrf_validate('x' . substr($token, 1), $token));
    }

    public function testEmptySubmittedTokenFails(): void
    {
        $this->assertFalse(csrf_validate('', csrf_new_token()));
    }

    public function testEmptyExpectedTokenFails(): void
    {
        $this->assertFalse(csrf_validate(csrf_new_token(), ''));
    }

    public function testTokensAreUnique(): void
    {
        $this->assertNotSame(csrf_new_token(), csrf_new_token());
    }
}