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
        require_auth();

        $project = $this->resolveProject();
        $projectId = (int) $project['id'];

        $error = '';
        $showProjectForm = false;
        $draft = '';
        $editId = null;
        $edit = null;
        $phrase = '';
        $search = trim((string) ($_GET['search'] ?? ''));
        $filters = $this->readFilters();

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = (string) ($_POST['action'] ?? '');
            $phrase = trim((string) ($_POST['phrase'] ?? ''));
            $postId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

            if ($action === 'create_project') {
                $showProjectForm = true;
                $result = $this->createProject();
                $error = $result['error'];
                if ($error === '') {
                    header('Location: index.php?project=' . (int) $result['project_id']);
                    exit;
                }
            } elseif ($action === 'create') {
                $draft = $phrase;
                $error = $this->create($projectId, $phrase);
            } elseif ($action === 'update') {
                $editId = $postId !== false ? $postId : null;
                $error = $this->update($projectId, $editId, $phrase);
            } elseif ($action === 'delete') {
                $error = $this->delete($projectId, $postId !== false ? $postId : null);
            } else {
                $error = 'Unknown action.';
            }

            if ($error === '') {
                header('Location: index.php?project=' . $projectId);
                exit;
            }
        }

        if ($editId === null) {
            $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
        }
        if ($editId !== null && $editId !== false) {
            $edit = keyword_find($this->pdo, $editId, $projectId);
            if ($edit !== null && $error !== '') {
                $edit['phrase'] = $phrase;
            }
            if ($edit === null) {
                $editId = null;
            }
        }

        $this->render($project, $error, $showProjectForm, $edit, $draft, $search, $filters);
    }

    private function readFilters(): array
    {
        $trend = (string) ($_GET['trend'] ?? '');
        if (!in_array($trend, ['improved', 'declined', 'stable'], true)) {
            $trend = '';
        }
        $from = filter_input(INPUT_GET, 'pos_from', FILTER_VALIDATE_INT);
        $to = filter_input(INPUT_GET, 'pos_to', FILTER_VALIDATE_INT);
        $posFrom = ($from !== null && $from !== false) ? max(1, min(100, $from)) : null;
        $posTo = ($to !== null && $to !== false) ? max(1, min(100, $to)) : null;
        if ($posFrom !== null && $posTo !== null && $posFrom > $posTo) {
            [$posFrom, $posTo] = [$posTo, $posFrom];
        }
        return ['trend' => $trend, 'pos_from' => $posFrom, 'pos_to' => $posTo];
    }

    private function resolveProject(): array
    {
        $requested = filter_input(INPUT_GET, 'project', FILTER_VALIDATE_INT);
        if ($requested === null || $requested === false) {
            $requested = filter_input(INPUT_POST, 'project', FILTER_VALIDATE_INT);
        }
        return project_resolve(
            $this->pdo,
            $this->config,
            $requested !== null && $requested !== false ? $requested : null
        );
    }

    private function createProject(): array
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $siteUrl = trim((string) ($_POST['site_url'] ?? ''));
        if ($name === '' || $siteUrl === '') {
            return ['error' => 'Project name and site URL are required.', 'project_id' => null];
        }
        if (strlen($name) > 100) {
            return ['error' => 'Project name must be 100 characters or fewer.', 'project_id' => null];
        }
        if (strlen($siteUrl) > 255) {
            return ['error' => 'Site URL must be 255 characters or fewer.', 'project_id' => null];
        }
        try {
            $id = project_create($this->pdo, $name, $siteUrl);
            return ['error' => '', 'project_id' => $id];
        } catch (PDOException $e) {
            if (str_starts_with((string) $e->getCode(), '23')) {
                return ['error' => 'A project with that name already exists.', 'project_id' => null];
            }
            throw $e;
        }
    }

    private function create(int $projectId, string $phrase): string
    {
        if ($phrase === '') {
            return 'Phrase is required.';
        }
        if (strlen($phrase) > 255) {
            return 'Phrase must be 255 characters or fewer.';
        }
        try {
            $id = keyword_create($this->pdo, $projectId, $phrase);
            seed_keyword_history($this->pdo, $id);
        } catch (PDOException $e) {
            if (str_starts_with((string) $e->getCode(), '23')) {
                return 'A keyword with that phrase already exists.';
            }
            throw $e;
        }
        return '';
    }

    private function update(int $projectId, ?int $id, string $phrase): string
    {
        if ($id === null) {
            return 'Invalid keyword.';
        }
        if (keyword_find($this->pdo, $id, $projectId) === null) {
            return 'Keyword not found.';
        }
        if ($phrase === '') {
            return 'Phrase is required.';
        }
        if (strlen($phrase) > 255) {
            return 'Phrase must be 255 characters or fewer.';
        }
        try {
            keyword_update($this->pdo, $id, $projectId, $phrase);
        } catch (PDOException $e) {
            if (str_starts_with((string) $e->getCode(), '23')) {
                return 'A keyword with that phrase already exists.';
            }
            throw $e;
        }
        return '';
    }

    private function delete(int $projectId, ?int $id): string
    {
        if ($id === null) {
            return 'Invalid keyword.';
        }
        keyword_delete($this->pdo, $id, $projectId);
        return '';
    }

    private function render(
        array $project,
        string $error,
        bool $showProjectForm,
        ?array $edit,
        string $draft,
        string $search,
        array $filters
    ): void {
        $projectId = (int) $project['id'];
        $projects = project_list($this->pdo);
        $allRows = keyword_rows_with_metrics($this->pdo, $projectId, $search);
        $rows = filter_rows($allRows, $filters['trend'], $filters['pos_from'], $filters['pos_to']);
        $filtersActive = $filters['trend'] !== '' || $filters['pos_from'] !== null || $filters['pos_to'] !== null;
        $filterQuery = '';
        if ($filters['trend'] !== '') {
            $filterQuery .= '&amp;trend=' . rawurlencode($filters['trend']);
        }
        if ($filters['pos_from'] !== null) {
            $filterQuery .= '&amp;pos_from=' . (int) $filters['pos_from'];
        }
        if ($filters['pos_to'] !== null) {
            $filterQuery .= '&amp;pos_to=' . (int) $filters['pos_to'];
        }
        $lastDate = position_last_date($this->pdo, $projectId);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>MiniRank — Keywords</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <?= csrf_meta() ?>
        </head>
        <body>
        <main>
            <h1>MiniRank</h1>
            <div class="toolbar">
                <p class="muted">Project: <strong><?= e($project['name']) ?></strong> — tracking positions for <?= e($project['site_url']) ?></p>
                <button type="button" id="refresh-btn" class="btn" data-project="<?= $projectId ?>">Refresh positions</button>
            </div>
            <p id="refresh-status" class="muted">
                <?php if ($lastDate !== null): ?>Last refreshed: <?= e($lastDate) ?><?php endif; ?>
            </p>
            <p class="muted session">Signed in as <strong><?= e(current_username()) ?></strong>
                <form method="post" action="logout.php" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn">Log out</button>
                </form>
            </p>

            <nav class="projects">
                <span class="muted">Switch project:</span>
                <?php foreach ($projects as $p): ?>
                    <a class="btn project-link<?= (int) $p['id'] === $projectId ? ' active' : '' ?>"
                       href="index.php?project=<?= (int) $p['id'] ?>"><?= e($p['name']) ?></a>
                <?php endforeach; ?>
            </nav>

            <details class="card"<?= $showProjectForm ? ' open' : '' ?>>
                <summary>+ New project</summary>
                <form method="post" action="index.php" class="project-form">
                    <input type="hidden" name="action" value="create_project">
                    <?= csrf_field() ?>
                    <input type="text" name="name" placeholder="Project name" maxlength="100" required>
                    <input type="text" name="site_url" placeholder="https://site.example" maxlength="255" required>
                    <button type="submit">Create project</button>
                </form>
            </details>

            <?php if ($error !== ''): ?>
                <p class="error"><?= e($error) ?></p>
            <?php endif; ?>

            <?php if ($edit !== null): ?>
                <form method="post" action="index.php" class="card">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
                    <input type="hidden" name="project" value="<?= $projectId ?>">
                    <?= csrf_field() ?>
                    <input type="text" name="phrase" value="<?= e($edit['phrase']) ?>" maxlength="255" required>
                    <button type="submit">Save</button>
                    <a class="btn" href="index.php?project=<?= $projectId ?>">Cancel</a>
                </form>
            <?php else: ?>
                <form method="post" action="index.php" class="card">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="project" value="<?= $projectId ?>">
                    <?= csrf_field() ?>
                    <input type="text" name="phrase" value="<?= e($draft) ?>" placeholder="New keyword phrase" maxlength="255" required>
                    <button type="submit">Add</button>
                </form>
            <?php endif; ?>

            <form method="get" action="index.php" class="search">
                <input type="hidden" name="project" value="<?= $projectId ?>">
                <?php if ($filters['trend'] !== ''): ?>
                    <input type="hidden" name="trend" value="<?= e($filters['trend']) ?>">
                <?php endif; ?>
                <?php if ($filters['pos_from'] !== null): ?>
                    <input type="hidden" name="pos_from" value="<?= (int) $filters['pos_from'] ?>">
                <?php endif; ?>
                <?php if ($filters['pos_to'] !== null): ?>
                    <input type="hidden" name="pos_to" value="<?= (int) $filters['pos_to'] ?>">
                <?php endif; ?>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search keywords…">
                <button type="submit" class="btn">Search</button>
                <?php if ($search !== ''): ?>
                    <a class="btn" href="index.php?project=<?= $projectId ?><?= $filterQuery ?>">Clear</a>
                <?php endif; ?>
            </form>

            <form method="get" action="index.php" class="filter">
                <input type="hidden" name="project" value="<?= $projectId ?>">
                <?php if ($search !== ''): ?>
                    <input type="hidden" name="search" value="<?= e($search) ?>">
                <?php endif; ?>
                <select name="trend" aria-label="Movement">
                    <option value="">Any movement</option>
                    <option value="improved"<?= $filters['trend'] === 'improved' ? ' selected' : '' ?>>▲ Improved</option>
                    <option value="declined"<?= $filters['trend'] === 'declined' ? ' selected' : '' ?>>▼ Declined</option>
                    <option value="stable"<?= $filters['trend'] === 'stable' ? ' selected' : '' ?>>─ Stable</option>
                </select>
                <input type="number" name="pos_from" min="1" max="100" placeholder="Rank from" value="<?= $filters['pos_from'] !== null ? (int) $filters['pos_from'] : '' ?>">
                <span class="muted">–</span>
                <input type="number" name="pos_to" min="1" max="100" placeholder="Rank to" value="<?= $filters['pos_to'] !== null ? (int) $filters['pos_to'] : '' ?>">
                <button type="submit" class="btn">Filter</button>
                <?php if ($filtersActive): ?>
                    <a class="btn" href="index.php?project=<?= $projectId ?><?= $search !== '' ? '&amp;search=' . rawurlencode($search) : '' ?>">Clear filters</a>
                <?php endif; ?>
            </form>

            <?php if ($filtersActive): ?>
                <p class="muted">Showing <?= count($rows) ?> of <?= count($allRows) ?> keyword<?= count($allRows) === 1 ? '' : 's' ?></p>
            <?php endif; ?>

            <div class="table-wrap">
            <table class="kw">
                <thead>
                <tr>
                    <th>Keyword</th>
                    <th>Position</th>
                    <th>Trend (7d)</th>
                    <th class="col-added">Added</th>
                    <th class="right">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $k): ?>
                    <tr data-keyword-id="<?= (int) $k['id'] ?>">
                        <td><a class="kw-link" href="keyword.php?id=<?= (int) $k['id'] ?>&amp;project=<?= $projectId ?>"><?= e($k['phrase']) ?></a></td>
                        <td class="pos"><?= $k['position'] !== null ? (int) $k['position'] : '—' ?></td>
                        <td class="trend <?= e($k['trend'] ?? '') ?>"><?= trend_label($k['trend']) ?></td>
                        <td class="col-added"><?= e($k['created_at']) ?></td>
                        <td class="right">
                            <a class="btn" href="index.php?project=<?= $projectId ?>&amp;edit=<?= (int) $k['id'] ?>">Edit</a>
                            <form method="post" action="index.php" class="inline"
                                  data-confirm="Delete this keyword and all its history?">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                                <input type="hidden" name="project" value="<?= $projectId ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($rows) === 0): ?>
                    <tr>
                        <td colspan="5" class="empty">
                            <?= $search !== ''
                                ? 'No keywords match your search.'
                                : ($filtersActive ? 'No keywords match your filters.' : 'No keywords yet — add your first one above.') ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </main>
        <script src="assets/js/app.js"></script>
        </body>
        </html>
        <?php
    }
}