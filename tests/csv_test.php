<?php

declare(strict_types=1);

eq("x,y,z\r\n", csv_encode([['x', 'y', 'z']]), 'simple row uses CRLF');

eq(
    "a,\"b,comma\",\"has \"\"quote\"\"\",\"line\nbreak\",,\r\n",
    csv_encode([
        ['a', 'b,comma', 'has "quote"', "line\nbreak", null, ''],
    ]),
    'commas, quotes, newlines quoted; empty fields stay empty'
);

eq('', csv_encode([]), 'no rows produces empty string');