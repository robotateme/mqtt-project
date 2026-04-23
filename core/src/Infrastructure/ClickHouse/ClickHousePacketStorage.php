<?php

declare(strict_types=1);

namespace Core\Infrastructure\ClickHouse;

use App\Support\ClickHouse\ClickHouseClient;
use Core\Application\Packets\PacketStoragePort;

final readonly class ClickHousePacketStorage implements PacketStoragePort
{
    public function __construct(
        private ClickHouseClient $client,
        private string $table,
    ) {
    }

    public function store(array $packets): void
    {
        $this->client->insertJsonEachRow($this->table, $packets);
    }
}
