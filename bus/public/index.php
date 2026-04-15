<?php

declare(strict_types=1);

use Bus\Support\RuntimeStatus;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config/config.php';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

header('Content-Type: application/json; charset=utf-8');

if ($path === '/health') {
    echo json_encode([
        'status' => 'ok',
        'service' => 'bus',
        'env' => $config['app']['env'],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    return;
}

if ($path === '/ready') {
    $status = RuntimeStatus::read($config['runtime']['status_file']);
    $updatedAt = isset($status['updated_at']) ? strtotime((string) $status['updated_at']) : false;
    $isFresh = $updatedAt !== false && time() - $updatedAt <= 10;

    http_response_code($isFresh ? 200 : 503);
    echo json_encode([
        'status' => $isFresh ? 'ready' : 'not_ready',
        'mqtt' => $config['mqtt']['host'] . ':' . $config['mqtt']['port'],
        'kafka' => $config['kafka']['brokers'],
        'worker' => $status,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    return;
}

http_response_code(404);
echo json_encode(['error' => 'not_found'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
