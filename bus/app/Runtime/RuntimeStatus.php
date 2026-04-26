<?php

declare(strict_types=1);

namespace Bus\Runtime;

use RuntimeException;

final class RuntimeStatus
{
    private int $lastWriteMs = 0;

    public function __construct(
        private string $path,
        private int $intervalMs,
        private string $busId,
    ) {
    }

    /**
     * @param array<string, mixed> $status
     */
    public function write(array $status, bool $force = false): void
    {
        $nowMs = (int) floor(microtime(true) * 1000.0);

        if (!$force && $nowMs - $this->lastWriteMs < $this->intervalMs) {
            return;
        }

        $directory = dirname($this->path);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create runtime directory: %s', $directory));
        }

        $payload = json_encode(
            $status + ['bus_id' => $this->busId, 'updated_at' => gmdate('c')],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
        );

        file_put_contents($this->path, $payload . PHP_EOL, LOCK_EX);
        $this->lastWriteMs = $nowMs;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public static function read(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $payload = file_get_contents($path);

        if ($payload === false) {
            return null;
        }

        /** @var mixed $decoded */
        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }
}
