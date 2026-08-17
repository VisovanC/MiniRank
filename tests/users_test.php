<?php

declare(strict_types=1);

$pdo = memory_pdo();
$config = ['db' => ['path' => ':memory:'], 'site' => ['url' => 'https://example.test']];
app_schema_init($pdo, __DIR__ . '/../schema.sql', $config);

eq(0, user_count($pdo), 'no users initially');
user_create($pdo, 'alice', 'secret123');
eq(1, user_count($pdo), 'user_create adds a user');
ok(user_verify($pdo, 'alice', 'secret123'), 'correct password verifies');
ok(!user_verify($pdo, 'alice', 'wrong'), 'wrong password rejected');
ok(!user_verify($pdo, 'ghost', 'secret123'), 'unknown user rejected');

$user = user_find_by_username($pdo, 'alice');
eq(0, (int) $user['failed_attempts'], 'starts with zero failures');

for ($i = 0; $i < 5; $i++) {
    user_record_failure($pdo, 'alice');
}
ok(user_is_locked($pdo, (int) $user['id']), 'locked after 5 failures');
eq(5, (int) user_find_by_username($pdo, 'alice')['failed_attempts'], 'failures recorded');
ok(user_verify($pdo, 'alice', 'secret123'), 'user_verify is password-only; the lock is enforced by the controller');

user_reset_failures($pdo, (int) $user['id']);
ok(!user_is_locked($pdo, (int) $user['id']), 'unlocked after reset');
eq(0, (int) user_find_by_username($pdo, 'alice')['failed_attempts'], 'reset clears failures');