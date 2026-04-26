<?php

declare(strict_types=1);

namespace Tests\Unit;

use Bus\Runtime\RuntimeStatus;
use PHPUnit\Framework\TestCase;

final class RuntimeStatusTest extends TestCase
{
    public function test_writes_bus_id_to_runtime_status(): void
    {
        $statusFile = sys_get_temp_dir() . '/bus-runtime-status-' . bin2hex(random_bytes(6)) . '.json';
        $status = new RuntimeStatus($statusFile, intervalMs: 1000, busId: 'bus-alpha');

        $status->write(['status' => 'running'], force: true);

        $payload = RuntimeStatus::read($statusFile);
        self::assertIsArray($payload);
        self::assertSame('running', $payload['status']);
        self::assertSame('bus-alpha', $payload['bus_id']);
        self::assertArrayHasKey('updated_at', $payload);

        unlink($statusFile);
    }

    public function test_skips_status_write_inside_interval_until_forced(): void
    {
        $statusFile = sys_get_temp_dir() . '/bus-runtime-status-' . bin2hex(random_bytes(6)) . '.json';
        $status = new RuntimeStatus($statusFile, intervalMs: 60000, busId: 'bus-alpha');

        $status->write(['status' => 'running'], force: true);
        $status->write(['status' => 'stale']);

        $payload = RuntimeStatus::read($statusFile);
        self::assertIsArray($payload);
        self::assertSame('running', $payload['status']);

        $status->write(['status' => 'stopped'], force: true);

        $payload = RuntimeStatus::read($statusFile);
        self::assertIsArray($payload);
        self::assertSame('stopped', $payload['status']);

        unlink($statusFile);
    }
}
