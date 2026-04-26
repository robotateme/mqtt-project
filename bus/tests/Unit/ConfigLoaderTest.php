<?php

declare(strict_types=1);

namespace Tests\Unit;

use Bus\Config\ConfigLoader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConfigLoaderTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $basePath = sys_get_temp_dir() . '/bus-config-' . bin2hex(random_bytes(6));

        if (!mkdir($basePath . '/config', 0775, true) && !is_dir($basePath . '/config')) {
            throw new RuntimeException('Unable to create temporary config directory.');
        }

        $this->basePath = $basePath;
    }

    protected function tearDown(): void
    {
        unset($_ENV['MQTT_HOST'], $_SERVER['MQTT_HOST']);
        $this->removeDirectory($this->basePath);
    }

    public function test_loads_dotenv_before_config_file(): void
    {
        file_put_contents($this->basePath . '/.env', "MQTT_HOST=dotenv-mosquitto\n");
        file_put_contents($this->basePath . '/config/config.php', $this->configPhp());

        $config = ConfigLoader::load($this->basePath);

        self::assertSame('dotenv-mosquitto', $config->mqtt->host);
    }

    private function configPhp(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

return [
    'mqtt' => [
        'host' => $_ENV['MQTT_HOST'] ?? 'mosquitto',
        'port' => 1883,
        'client_id' => 'bus-test',
        'topic' => '#',
        'qos' => 1,
        'clean_session' => false,
        'username' => null,
        'password' => null,
    ],
    'kafka' => [
        'brokers' => 'kafka:9092',
        'topic' => 'mqtt.events',
        'batch_size' => 100,
        'linger_ms' => 100,
        'max_outstanding' => 10000,
        'backpressure_timeout_ms' => 5000,
        'message_timeout_ms' => 30000,
    ],
    'redis' => [
        'host' => 'redis',
        'port' => 6379,
        'password' => null,
        'database' => 0,
        'timeout' => 2.5,
    ],
    'outbox' => [
        'stream' => 'mqtt:outbox',
        'group' => 'bus-publishers',
        'consumer' => 'bus-test',
        'bus_id' => 'bus-test',
        'batch_size' => 100,
        'max_length' => 100000,
        'dedupe_ttl_seconds' => 86400,
        'block_ms' => 1,
    ],
    'runtime' => [
        'status_file' => __DIR__ . '/../storage/runtime/status.json',
        'status_interval_ms' => 1000,
    ],
];
PHP;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
