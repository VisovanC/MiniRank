<?php

declare(strict_types=1);

final class KeywordListController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function handle(): void
    {
        $error = '';
        $draft = '';
        $editId = null;
        $edit = null;
        $phrase = '';
        $search = trim((string) ($_GET['search'] ?? ''));

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = (string) ($_POST['action'] ?? '');
            $phrase = trim((string) ($_POST['phrase'] ?? ''));
            $postId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

            if ($action === 'create') {
                $draft = $phrase;
                $error = $this->create($phrase);
            } elseif ($action === 'update') {
                $editId = $postId !== false ? $postId : null;
                $error = $this->update($editId, $phrase);
            } elseif ($action === 'delete') {
                $error = $this->delete($postId);
            } else {
                $error = 'Unknown action.';
            }

            if ($error === '') {
                header('Location: index.php');
                exit;
            }
        }

        if ($editId === null) {
            $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
        }
        if ($editId !== null && $editId !== false) {
            $edit = keyword_find($this->pdo, $editId);
            if ($edit !== null && $error !== '') {
                $edit['phrase'] = $phrase;
            }
            if ($edit === null) {
                $editId = null;
            }
        }

        $this->render($error, $edit, $draft, $search);
    }

    private function create(string $phrase): string
    {
        if ($phrase === '') {
            return 'Phrase is required.';
        }
        if (strlen($phrase) > 255) {
            return 'Phrase must be 255 characters or fewer.';
        }
        try {
            keyword_create($this->pdo, $phrase);
        } catch (PDOException $e) {
            if (str_starts_with((string) $e->getCode(), '23')) {
                return 'A keyword with that phrase already exists.';
            }
            throw $e;
        }
        return '';
    }

    private function update(?int $id, string $phrase): string
    {
        if ($id === null) {
            return 'Invalid keyword.';
        }
        if (keyword_find($this->pdo, $id) === null) {
            return 'Keyword not found.';
        }
        if ($phrase === '') {
            return 'Phrase is required.';
        }
        if (strlen($phrase) > 255) {
            return 'Phrase must be 255 characters or fewer.';
        }
        try {
            keyword_update($this->pdo, $id, $phrase);
        } catch (PDOException $e) {
            if (str_starts_with((string) $e->getCode(), '23')) {
                return 'A keyword with that phrase already exists.';
            }
            throw $e;
        }
        return '';
    }

    private function delete(?int $id): string
    {
        if ($id === null) {
            return 'Invalid keyword.';
        }
        keyword_delete($this->pdo, $id);
        return '';
    }

    private function render(string $error, ?array $edit, string $draft, string $search): void
    {
        $keywords = keyword_list($this->pdo, $search);
        $histories = all_keyword_histories($this->pdo);
        $rows = [];
        foreach ($keywords as $k) {
            $history = $histories[(int) $k['id']] ?? [];
            $rows[] = [
                'id' => (int) $k['id'],
                'phrase' => $k['phrase'],
                'created_at' => $k['created_at'],
                'position' => latest_position($history),
                'trend' => trend_from_history($history),
            ];
        }
        $siteUrl = e($this->config['site']['url']);
        $lastDate = position_last_date($this->pdo);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>MiniRank — Keywords</title>
            <link rel="stylesheet" href="assets/css/style.css">
        </head>
        <body>
        <main>
            <h1>MiniRank</h1>
            <div class="toolbar">
                <p class="muted">Tracking positions for <?= $siteUrl ?></p>
                <button type="button" id="refresh-btn" class="btn">Refresh positions</button>
            </div>
            <p id="refresh-status" class="muted">
                <?php if ($lastDate !== null): ?>Last refreshed: <?= e($lastDate) ?><?php endif; ?>
            </p>

            <?php if ($error !== ''): ?>
                <p class="error"><?= e($error) ?></p>
            <?php endif; ?>

            <?php if ($edit !== null): ?>
                <form method="post" action="index.php" class="card">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
                    <input type="text" name="phrase" value="<?= e($edit['phrase']) ?>" maxlength="255" required>
                    <button type="submit">Save</button>
                    <a class="btn" href="index.php">Cancel</a>
                </form>
            <?php else: ?>
                <form method="post" action="index.php" class="card">
                    <input type="hidden" name="action" value="create">
                    <input type="text" name="phrase" value="<?= e($draft) ?>" placeholder="New keyword phrase" maxlength="255" required>
                    <button type="submit">Add</button>
                </form>
            <?php endif; ?>

            <form method="get" action="index.php" class="search">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search keywords…">
                <button type="submit" class="btn">Search</button>
                <?php if ($search !== ''): ?>
                    <a class="btn" href="index.php">Clear</a>
                <?php endif; ?>
            </form>

            <table class="kw">
                <thead>
                <tr>
                    <th>Keyword</th>
                    <th>Position</th>
                    <th>Trend (7d)</th>
                    <th>Added</th>
                    <th class="right">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $k): ?>
                    <tr>
                        <td><?= e($k['phrase']) ?></td>
                        <td><?= $k['position'] !== null ? (int) $k['position'] : '—' ?></td>
                        <td class="trend <?= e($k['trend'] ?? '') ?>"><?= $this->trendLabel($k['trend']) ?></td>
                        <td><?= e($k['created_at']) ?></td>
                        <td class="right">
                            <a class="btn" href="index.php?edit=<?= (int) $k['id'] ?>">Edit</a>
                            <form method="post" action="index.php" class="inline"
                                  onsubmit="return confirm('Delete this keyword and all its history?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($rows) === 0): ?>
                    <tr>
                        <td colspan="5" class="empty">
                            <?= $search !== '' ? 'No keywords match your search.' : 'No keywords yet — add your first one above.' ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </main>
        <script src="assets/js/app.js"></script>
        </body>
        </html>
        <?php
    }

    private function trendLabel(?string $trend): string
    {
        return match ($trend) {
            'improved' => '▲ Improved',
            'declined' => '▼ Declined',
            'stable' => '─ Stable',
            default => '—',
        };
    }
}