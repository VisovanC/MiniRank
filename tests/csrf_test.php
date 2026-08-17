<?php

declare(strict_types=1);

$t = csrf_new_token();
eq(64, strlen($t), 'token is 64 hex chars');
ok(csrf_validate($t, $t), 'valid token passes');
ok(!csrf_validate('x' . substr($t, 1), $t), 'tampered token fails');
ok(!csrf_validate('', $t), 'empty submitted token fails');
ok(!csrf_validate($t, ''), 'empty expected token fails');

$t2 = csrf_new_token();
ok($t !== $t2, 'tokens are unique');