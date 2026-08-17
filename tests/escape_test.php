<?php

declare(strict_types=1);

eq('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'), 'angle brackets escaped');
eq('&quot;&#039;&amp;', e("\"'&"), 'quotes and ampersand escaped');
eq('', e(null), 'null becomes empty string');
eq('plain text', e('plain text'), 'plain text unchanged');
eq('a&amp;b', e('a&b'), 'ampersand escaped');