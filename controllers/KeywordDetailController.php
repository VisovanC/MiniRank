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
        require_auth();

        $project = $this->resolveProject();
        $projectId = (int) $project['id'];
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $keyword = ($id !== null && $id !== false) ? keyword_find($this->pdo, $id, $projectId) : null;

        if ($keyword === null) {
            http_response_code(404);
        }

        $this->render($project, $keyword);
    }

    private function resolveProject(): array
    {
        $requested = filter_input(INPUT_GET, 'project', FILTER_VALIDATE_INT);
        return project_resolve(
            $this->pdo,
            $this->config,
            $requested !== null && $requested !== false ? $requested : null
        );
    }

    private function render(array $project, ?array $keyword): void
    {
        $projectId = (int) $project['id'];
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
            <p class="muted">Project: <strong><?= e($project['name']) ?></strong> — tracking positions for <?= e($project['site_url']) ?></p>
            <p><a class="btn" href="index.php?project=<?= $projectId ?>">&larr; Back to keywords</a></p>

            <?php if ($keyword === null): ?>
                <p class="error">Keyword not found.</p>
            <?php else: ?>
                <h2><?= e($keyword['phrase']) ?></h2>
                <p>
                    <a class="btn" href="export.php?type=history&amp;id=<?= (int) $keyword['id'] ?>&amp;project=<?= $projectId ?>">Export history CSV</a>
                </p>
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