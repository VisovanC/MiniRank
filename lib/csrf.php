<?php

declare(strict_types=1);

function csrf_new_token(): string
{
    return bin2hex(random_bytes(32));
}

function csrf_validate(string $submitted, string $expected): bool
{
    return $expected !== '' && hash_equals($expected, $submitted);
}