<?php

declare(strict_types=1);

final class RegisterController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function handle(): void
    {
        if (current_user_id() !== null) {
            header('Location: index.php');
            exit;
        }

        $error = '';
        $username = '';
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $confirm = (string) ($_POST['password_confirm'] ?? '');
            $error = $this->register($username, $password, $confirm);
        }

        $this->render($error, $username);
    }

    private function register(string $username, string $password, string $confirm): string
    {
        if (strlen($username) < 3) {
            return 'Username must be at least 3 characters.';
        }
        if (strlen($username) > 50) {
            return 'Username must be 50 characters or fewer.';
        }
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters.';
        }
        if (strlen($password) > 255) {
            return 'Password must be 255 characters or fewer.';
        }
        if ($password !== $confirm) {
            return 'Passwords do not match.';
        }
        try {
            $id = user_create($this->pdo, $username, $password);
        } catch (PDOException $e) {
            if (str_starts_with((string) $e->getCode(), '23')) {
                return 'Username is already taken.';
            }
            throw $e;
        }
        session_regenerate_id(true);
        $_SESSION['csrf'] = csrf_new_token();
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $username;
        header('Location: index.php');
        exit;
    }

    private function render(string $error, string $username): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>MiniRank — Create account</title>
            <link rel="stylesheet" href="assets/css/style.css">
        </head>
        <body>
        <main>
            <h1>MiniRank</h1>
            <div class="card auth">
                <h2>Create account</h2>
                <?php if ($error !== ''): ?>
                    <p class="error"><?= e($error) ?></p>
                <?php endif; ?>
                <form method="post" action="register.php">
                    <?= csrf_field() ?>
                    <input type="text" name="username" value="<?= e($username) ?>" placeholder="Username" maxlength="50" required autofocus>
                    <input type="password" name="password" placeholder="Password (min 8 characters)" maxlength="255" required>
                    <input type="password" name="password_confirm" placeholder="Confirm password" maxlength="255" required>
                    <button type="submit">Create account</button>
                </form>
                <p class="muted">Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </main>
        </body>
        </html>
        <?php
    }
}