<?php

declare(strict_types=1);

final class KeywordDetailController
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
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $keyword = ($id !== null && $id !== false) ? keyword_find($this->pdo, $id) : null;

        if ($keyword === null) {
            http_response_code(404);
        }

        $this->render($keyword);
    }

    private function render(?array $keyword): void
    {
        $siteUrl = e($this->config['site']['url']);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>MiniRank — Keyword</title>
            <link rel="stylesheet" href="assets/css/style.css">
        </head>
        <body>
        <main>
            <h1>MiniRank</h1>
            <p class="muted">Tracking positions for <?= $siteUrl ?></p>
            <p><a class="btn" href="index.php">&larr; Back to keywords</a></p>

            <?php if ($keyword === null): ?>
                <p class="error">Keyword not found.</p>
            <?php else: ?>
                <h2><?= e($keyword['phrase']) ?></h2>
                <?php
                $history = positions_for_keyword($this->pdo, (int) $keyword['id']);
                $current = $history !== [] ? (int) $history[0]['position'] : null;
                ?>
                <p class="muted">
                    <?php if ($current !== null): ?>
                        Current position: <?= $current ?> &middot;
                    <?php endif; ?>
                    <?= count($history) ?> record(s)
                </p>

                <?= chart_svg(array_reverse($history)) ?>

                <table class="kw">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Position</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?= e($row['date']) ?></td>
                            <td><?= (int) $row['position'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($history === []): ?>
                        <tr>
                            <td colspan="2" class="empty">No position history yet — click Refresh positions on the list page.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
        </body>
        </html>
        <?php
    }
}