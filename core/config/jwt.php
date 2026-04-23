<?php

declare(strict_types=1);

return [
    'secret' => env('JWT_SECRET', env('APP_KEY')),
    'issuer' => env('JWT_ISSUER', env('APP_URL', 'http://core.localhost')),
    'audience' => env('JWT_AUDIENCE', env('APP_URL', 'http://core.localhost')),
    'ttl_minutes' => (int) env('JWT_TTL_MINUTES', 60),
    'refresh_ttl_minutes' => (int) env('JWT_REFRESH_TTL_MINUTES', 10080),
    'leeway_seconds' => (int) env('JWT_LEEWAY_SECONDS', 30),
];
