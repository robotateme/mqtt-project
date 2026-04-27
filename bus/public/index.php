<?php

declare(strict_types=1);

use Bus\Config\Loader\ConfigLoader;
use Bus\Metrics\MetricsFactory;
use Bus\Runtime\RuntimeStatus;

require dirname(__DIR__) . '/vendor/autoload.php';

$typedConfig = ConfigLoader::load(dirname(__DIR__));
$config = require dirname(__DIR__) . '/config/config.php';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($requestPath) && $requestPath !== '' ? $requestPath : '/';

header('Content-Type: application/json; charset=utf-8');

if ($path === '/health') {
    echo json_encode([
        'status' => 'ok',
        'service' => 'bus',
        'env' => $config['app']['env'],
        'bus_id' => $config['app']['bus_id'],
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
        'bus_id' => $config['app']['bus_id'],
        'worker' => $status,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    return;
}

if ($path === '/metrics') {
    header('Content-Type: ' . MetricsFactory::contentType());
    echo MetricsFactory::render($typedConfig);
    return;
}

http_response_code(404);
echo json_encode(['error' => 'not_found'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
