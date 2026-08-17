<?php

declare(strict_types=1);

final class ExportController
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
        $type = (string) ($_GET['type'] ?? 'keywords');
        if ($type === 'history') {
            $this->exportHistory();
            return;
        }
        $this->exportKeywords();
    }

    private function exportKeywords(): void
    {
        $project = $this->resolveProject();
        $projectId = (int) $project['id'];
        $search = trim((string) ($_GET['search'] ?? ''));
        $filters = normalize_filters($_GET);
        $rows = filter_rows(
            keyword_rows_with_metrics($this->pdo, $projectId, $search),
            $filters['trend'],
            $filters['pos_from'],
            $filters['pos_to']
        );
        $out = [['Keyword', 'Position', 'Trend (7d)', 'Added']];
        foreach ($rows as $r) {
            $out[] = [
                $r['phrase'],
                $r['position'] !== null ? (int) $r['position'] : '',
                $r['trend'] ?? '',
                $r['created_at'],
            ];
        }
        $this->send('minirank-keywords.csv', $out);
    }

    private function exportHistory(): void
    {
        $project = $this->resolveProject();
        $projectId = (int) $project['id'];
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $keyword = ($id !== null && $id !== false) ? keyword_find($this->pdo, $id, $projectId) : null;
        if ($keyword === null) {
            http_response_code(404);
            exit;
        }
        $history = positions_for_keyword($this->pdo, (int) $keyword['id']);
        $out = [['Date', 'Position']];
        foreach ($history as $h) {
            $out[] = [$h['date'], (int) $h['position']];
        }
        $this->send('minirank-history-' . (int) $keyword['id'] . '.csv', $out);
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

    private function send(string $filename, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        echo "\xEF\xBB\xBF";
        echo csv_encode($rows);
        exit;
    }
}