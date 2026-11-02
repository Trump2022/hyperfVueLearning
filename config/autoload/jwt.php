<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'secret' => env('JWT_SECRET', ''),
    'ttl' => (int) env('JWT_TTL', 3600),
    'issuer' => env('APP_NAME', 'hyperfVueLearning'),
];