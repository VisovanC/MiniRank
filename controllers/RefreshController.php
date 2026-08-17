<?php

declare(strict_types=1);

final class RefreshController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function handle(): void
    {
        header('Content-Type: application/json');

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
            return;
        }

        $result = refresh_today($this->pdo, static fn(): float => mt_rand() / mt_getrandmax());

        $rows = [];
        foreach (keyword_rows_with_metrics($this->pdo) as $row) {
            $rows[] = [
                'id' => $row['id'],
                'position' => $row['position'],
                'trend' => $row['trend'],
                'trend_label' => trend_label($row['trend']),
            ];
        }

        echo json_encode([
            'ok' => true,
            'date' => $result['date'],
            'count' => $result['count'],
            'rows' => $rows,
        ]);
    }
}