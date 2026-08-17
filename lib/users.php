<?php

declare(strict_types=1);

const USER_MAX_FAILED_ATTEMPTS = 5;
const USER_LOCK_MINUTES = 5;

function user_count(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

function user_find_by_username(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, username, password_hash, failed_attempts, locked_until, created_at FROM users WHERE username = :username'
    );
    $stmt->execute(['username' => $username]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function user_create(PDO $pdo, string $username, string $password): int
{
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)');
    $stmt->execute(['username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
    return (int) $pdo->lastInsertId();
}

function user_verify(PDO $pdo, string $username, string $password): bool
{
    $user = user_find_by_username($pdo, $username);
    $hash = $user !== null
        ? $user['password_hash']
        : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    return password_verify($password, $hash);
}

function user_is_locked(PDO $pdo, int $userId): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM users
         WHERE id = :id AND locked_until IS NOT NULL AND locked_until > datetime('now')"
    );
    $stmt->execute(['id' => $userId]);
    return (int) $stmt->fetchColumn() > 0;
}

function user_record_failure(PDO $pdo, string $username): void
{
    $user = user_find_by_username($pdo, $username);
    if ($user === null) {
        return;
    }
    $stmt = $pdo->prepare('UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = :id');
    $stmt->execute(['id' => (int) $user['id']]);

    $attempts = (int) $user['failed_attempts'] + 1;
    if ($attempts >= USER_MAX_FAILED_ATTEMPTS) {
        $stmt = $pdo->prepare("UPDATE users SET locked_until = datetime('now', '+5 minutes') WHERE id = :id");
        $stmt->execute(['id' => (int) $user['id']]);
    }
}

function user_reset_failures(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = :id');
    $stmt->execute(['id' => $userId]);
}