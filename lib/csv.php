<?php

declare(strict_types=1);

function csv_encode(array $rows): string
{
    $out = '';
    foreach ($rows as $row) {
        $fields = [];
        foreach ($row as $value) {
            $value = (string) $value;
            if (strpbrk($value, ",\"\r\n") !== false) {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            $fields[] = $value;
        }
        $out .= implode(',', $fields) . "\r\n";
    }
    return $out;
}