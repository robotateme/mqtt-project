<?php

declare(strict_types=1);

return [
    'public_url' => (string) env('MERCURE_PUBLIC_URL', 'http://localhost:1337/.well-known/mercure'),
    'internal_url' => (string) env('MERCURE_INTERNAL_URL', 'http://mercure/.well-known/mercure'),
    'publisher_jwt_key' => (string) env('MERCURE_PUBLISHER_JWT_KEY', 'secret'),
    'subscriber_jwt_key' => (string) env('MERCURE_SUBSCRIBER_JWT_KEY', 'another_secret'),
    'jwt_algorithm' => (string) env('MERCURE_JWT_ALGORITHM', 'HS256'),
];
