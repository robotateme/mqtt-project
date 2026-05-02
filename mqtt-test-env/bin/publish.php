#!/usr/bin/env php
<?php

declare(strict_types=1);

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

$projectRoot = dirname(__DIR__, 2);
$autoloadPath = $projectRoot . '/bus/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    fwrite(STDERR, "bus/vendor/autoload.php not found. Run `make bus-install` first.\n");
    exit(1);
}

require $autoloadPath;

loadLocalEnv(__DIR__ . '/../.env');

$options = getopt('', [
    'scenario::',
    'host::',
    'port::',
    'client-id::',
    'username::',
    'password::',
    'qos::',
    'retain',
    'delay-ms::',
    'repeat::',
    'help',
]);

if (isset($options['help'])) {
    printHelp();
    exit(0);
}

try {
    $scenarioPath = resolvePath(option('scenario', 'MQTT_TEST_SCENARIO', 'mqtt-test-env/scenarios/device-demo.json'), $projectRoot);
    $scenario = loadScenario($scenarioPath);

    $host = option('host', 'MQTT_TEST_HOST', 'mosquitto');
    $port = boundedInt(option('port', 'MQTT_TEST_PORT', '1883'), 1, 65535, 'port');
    $clientId = option('client-id', 'MQTT_TEST_CLIENT_ID', 'mqtt-test-device-demo-001');
    $qos = boundedInt(option('qos', 'MQTT_TEST_QOS', '1'), 0, 2, 'qos');
    $delayMs = boundedInt(option('delay-ms', 'MQTT_TEST_DELAY_MS', '750'), 0, 60000, 'delay-ms');
    $repeat = boundedInt(option('repeat', 'MQTT_TEST_REPEAT', '1'), 1, 1000000, 'repeat');
    $retain = isset($options['retain']) || boolOption('MQTT_TEST_RETAIN', false);
    $username = nullableOption('username', 'MQTT_TEST_USERNAME');
    $password = nullableOption('password', 'MQTT_TEST_PASSWORD');

    $connectionSettings = new ConnectionSettings()
        ->setUsername($username)
        ->setPassword($password)
        ->setConnectTimeout(10)
        ->setSocketTimeout(5)
        ->setKeepAliveInterval(10)
        ->useBlockingSocket(true);

    $mqtt = new MqttClient($host, $port, $clientId);

    printf(
        "Connecting to mqtt://%s:%d as %s, scenario %s\n",
        $host,
        $port,
        $clientId,
        $scenario['name'],
    );

    $mqtt->connect($connectionSettings, true);

    for ($iteration = 1; $iteration <= $repeat; $iteration++) {
        foreach ($scenario['packets'] as $index => $packet) {
            publishPacket($mqtt, $packet, $qos, $retain, $iteration, $index + 1);

            $packetDelayMs = array_key_exists('delay_ms', $packet)
                ? boundedInt((string) $packet['delay_ms'], 0, 60000, 'packet.delay_ms')
                : $delayMs;

            if ($packetDelayMs > 0) {
                usleep($packetDelayMs * 1000);
            }
        }
    }

    $mqtt->disconnect();
    printf("Published %d packet(s) in %d iteration(s).\n", count($scenario['packets']), $repeat);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

/**
 * @return array{name: string, packets: list<array<string, mixed>>}
 */
function loadScenario(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException(sprintf('Scenario file not found: %s', $path));
    }

    $raw = file_get_contents($path);

    if ($raw === false) {
        throw new RuntimeException(sprintf('Unable to read scenario file: %s', $path));
    }

    try {
        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException(sprintf('Invalid scenario JSON: %s', $exception->getMessage()), previous: $exception);
    }

    if (!is_array($data)) {
        throw new RuntimeException('Scenario must be a JSON object.');
    }

    $name = $data['name'] ?? basename($path);
    $packets = $data['packets'] ?? null;

    if (!is_string($name) || $name === '') {
        throw new RuntimeException('Scenario field "name" must be a non-empty string.');
    }

    if (!is_array($packets) || $packets === []) {
        throw new RuntimeException('Scenario field "packets" must be a non-empty array.');
    }

    foreach ($packets as $index => $packet) {
        if (!is_array($packet)) {
            throw new RuntimeException(sprintf('Packet #%d must be a JSON object.', $index + 1));
        }

        if (!isset($packet['topic']) || !is_string($packet['topic']) || $packet['topic'] === '') {
            throw new RuntimeException(sprintf('Packet #%d field "topic" must be a non-empty string.', $index + 1));
        }

        if (!array_key_exists('payload', $packet)) {
            throw new RuntimeException(sprintf('Packet #%d must contain field "payload".', $index + 1));
        }
    }

    return [
        'name' => $name,
        'packets' => array_values($packets),
    ];
}

/**
 * @param array<string, mixed> $packet
 */
function publishPacket(
    MqttClient $mqtt,
    array $packet,
    int $defaultQos,
    bool $defaultRetain,
    int $iteration,
    int $number,
): void {
    $topic = (string) $packet['topic'];
    $payload = buildPayload($packet);
    $qos = array_key_exists('qos', $packet)
        ? boundedInt((string) $packet['qos'], 0, 2, 'packet.qos')
        : $defaultQos;
    $retain = array_key_exists('retain', $packet)
        ? boolValue($packet['retain'])
        : $defaultRetain;

    $mqtt->publish($topic, $payload, $qos, $retain);

    printf(
        "[%d:%d] topic=%s qos=%d retain=%s bytes=%d payload=%s\n",
        $iteration,
        $number,
        $topic,
        $qos,
        $retain ? 'true' : 'false',
        strlen($payload),
        printablePayload($payload),
    );
}

/**
 * @param array<string, mixed> $packet
 */
function buildPayload(array $packet): string
{
    $payload = $packet['payload'];
    $encoding = $packet['encoding'] ?? 'plain';

    if (!is_string($encoding)) {
        throw new RuntimeException('Packet field "encoding" must be a string.');
    }

    if ($encoding === 'base64') {
        if (!is_string($payload)) {
            throw new RuntimeException('Base64 packet payload must be a string.');
        }

        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            throw new RuntimeException('Base64 packet payload is invalid.');
        }

        return $decoded;
    }

    if ($encoding !== 'plain') {
        throw new RuntimeException(sprintf('Unsupported packet encoding: %s', $encoding));
    }

    if (is_array($payload)) {
        try {
            return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('Unable to encode packet payload: %s', $exception->getMessage()), previous: $exception);
        }
    }

    if (is_bool($payload)) {
        return $payload ? 'true' : 'false';
    }

    if (is_scalar($payload) || $payload === null) {
        return (string) $payload;
    }

    throw new RuntimeException('Packet payload must be scalar, null or object/array.');
}

function option(string $cliKey, string $envKey, string $default): string
{
    global $options;

    $value = $options[$cliKey] ?? getenv($envKey);

    if (is_array($value)) {
        $value = end($value);
    }

    return is_scalar($value) && (string) $value !== '' ? (string) $value : $default;
}

function nullableOption(string $cliKey, string $envKey): ?string
{
    global $options;

    $value = $options[$cliKey] ?? getenv($envKey);

    if (is_array($value)) {
        $value = end($value);
    }

    if (!is_scalar($value) || (string) $value === '') {
        return null;
    }

    return (string) $value;
}

function boolOption(string $envKey, bool $default): bool
{
    $value = getenv($envKey);

    if ($value === false || $value === '') {
        return $default;
    }

    return boolValue($value);
}

function boolValue(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (!is_scalar($value)) {
        throw new RuntimeException('Boolean value must be scalar.');
    }

    $bool = filter_var((string) $value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

    if ($bool === null) {
        throw new RuntimeException(sprintf('Invalid boolean value: %s', (string) $value));
    }

    return $bool;
}

function boundedInt(string $value, int $min, int $max, string $name): int
{
    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
        throw new RuntimeException(sprintf('Option "%s" must be an integer.', $name));
    }

    $int = (int) $value;

    if ($int < $min || $int > $max) {
        throw new RuntimeException(sprintf('Option "%s" must be between %d and %d.', $name, $min, $max));
    }

    return $int;
}

function resolvePath(string $path, string $projectRoot): string
{
    if (str_starts_with($path, '/')) {
        return $path;
    }

    return $projectRoot . '/' . ltrim($path, '/');
}

function printablePayload(string $payload): string
{
    if (preg_match('/^[\x20-\x7E\r\n\t]*$/', $payload) === 1) {
        return str_replace(["\r", "\n", "\t"], ['\r', '\n', '\t'], $payload);
    }

    return 'base64:' . base64_encode($payload);
}

function loadLocalEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        throw new RuntimeException(sprintf('Unable to read env file: %s', $path));
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);

        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        $value = trim($value);
        $value = trim($value, "\"'");

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function printHelp(): void
{
    echo <<<'HELP'
Usage:
  php mqtt-test-env/bin/publish.php [options]

Options:
  --scenario=PATH   Scenario JSON path.
  --host=HOST       Mosquitto host.
  --port=PORT       Mosquitto port.
  --client-id=ID    MQTT client id.
  --username=USER   MQTT username.
  --password=PASS   MQTT password.
  --qos=0|1|2       Default QoS.
  --retain          Retain all packets by default.
  --delay-ms=MS     Delay between packets.
  --repeat=N        Repeat scenario N times.
  --help            Show this help.

HELP;
}
