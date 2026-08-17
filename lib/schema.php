<?php

declare(strict_types=1);

function app_schema_init(PDO $pdo, string $schemaFile): void
{
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'keywords'");
    if ($stmt->fetch() !== false) {
        return;
    }
    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        throw new RuntimeException('Schema file not found: ' . $schemaFile);
    }
    $pdo->exec($sql);
}