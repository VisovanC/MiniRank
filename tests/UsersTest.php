<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UsersTest extends TestCase
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

    public function testCreateUser(): void
    {
        $this->assertSame(0, user_count($this->pdo));
        user_create($this->pdo, 'alice', 'secret123');
        $this->assertSame(1, user_count($this->pdo));
    }

    public function testPasswordVerification(): void
    {
        user_create($this->pdo, 'alice', 'secret123');
        $this->assertTrue(user_verify($this->pdo, 'alice', 'secret123'));
        $this->assertFalse(user_verify($this->pdo, 'alice', 'wrong'));
        $this->assertFalse(user_verify($this->pdo, 'ghost', 'secret123'));
    }

    public function testStartsWithZeroFailures(): void
    {
        user_create($this->pdo, 'alice', 'secret123');
        $user = user_find_by_username($this->pdo, 'alice');
        $this->assertSame(0, (int) $user['failed_attempts']);
    }

    public function testLockoutAfterFiveFailures(): void
    {
        user_create($this->pdo, 'alice', 'secret123');
        $user = user_find_by_username($this->pdo, 'alice');
        for ($i = 0; $i < 5; $i++) {
            user_record_failure($this->pdo, 'alice');
        }
        $this->assertTrue(user_is_locked($this->pdo, (int) $user['id']));
        $this->assertSame(5, (int) user_find_by_username($this->pdo, 'alice')['failed_attempts']);
    }

    public function testResetClearsFailuresAndLock(): void
    {
        user_create($this->pdo, 'alice', 'secret123');
        $user = user_find_by_username($this->pdo, 'alice');
        for ($i = 0; $i < 5; $i++) {
            user_record_failure($this->pdo, 'alice');
        }
        user_reset_failures($this->pdo, (int) $user['id']);
        $this->assertFalse(user_is_locked($this->pdo, (int) $user['id']));
        $this->assertSame(0, (int) user_find_by_username($this->pdo, 'alice')['failed_attempts']);
    }

    public function testVerifyIsPasswordOnlyWhileLockEnforcementLivesInController(): void
    {
        user_create($this->pdo, 'alice', 'secret123');
        $user = user_find_by_username($this->pdo, 'alice');
        for ($i = 0; $i < 5; $i++) {
            user_record_failure($this->pdo, 'alice');
        }
        $this->assertTrue(user_is_locked($this->pdo, (int) $user['id']));
        $this->assertTrue(user_verify($this->pdo, 'alice', 'secret123'));
    }
}