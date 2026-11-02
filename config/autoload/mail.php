<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'dsn' => env('MAILER_DSN', ''),
    'from_address' => env('MAIL_FROM_ADDRESS', ''),
    'from_name' => env('MAIL_FROM_NAME', env('APP_NAME', 'HyPerf Vue')),
    'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/'),
];