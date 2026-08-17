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
        echo json_encode([
            'ok' => true,
            'date' => $result['date'],
            'count' => $result['count'],
        ]);
    }
}