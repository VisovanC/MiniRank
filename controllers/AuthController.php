<?php

declare(strict_types=1);

final class AuthController
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
        if (user_count($this->pdo) === 0) {
            header('Location: register.php');
            exit;
        }

        $error = '';
        $username = '';
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $user = user_find_by_username($this->pdo, $username);
            if ($user !== null && user_is_locked($this->pdo, (int) $user['id'])) {
                $error = 'Too many failed attempts. Account locked for ' . USER_LOCK_MINUTES . ' minutes.';
            } elseif (user_verify($this->pdo, $username, $password)) {
                user_reset_failures($this->pdo, (int) $user['id']);
                session_regenerate_id(true);
                $_SESSION['csrf'] = csrf_new_token();
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: index.php');
                exit;
            } else {
                user_record_failure($this->pdo, $username);
                $error = 'Invalid username or password.';
            }
        }

        $this->render($error, $username);
    }

    private function render(string $error, string $username): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>MiniRank — Sign in</title>
            <link rel="stylesheet" href="assets/css/style.css">
        </head>
        <body>
        <main>
            <h1>MiniRank</h1>
            <div class="card auth">
                <h2>Sign in</h2>
                <?php if ($error !== ''): ?>
                    <p class="error"><?= e($error) ?></p>
                <?php endif; ?>
                <form method="post" action="login.php">
                    <?= csrf_field() ?>
                    <input type="text" name="username" value="<?= e($username) ?>" placeholder="Username" maxlength="50" required autofocus>
                    <input type="password" name="password" placeholder="Password" maxlength="255" required>
                    <button type="submit">Sign in</button>
                </form>
                <p class="muted">No account yet? <a href="register.php">Create one</a></p>
            </div>
        </main>
        </body>
        </html>
        <?php
    }
}