<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\ClickHouse\ClickHouseClient;
use Illuminate\Console\Command;

final class MigrateClickHouse extends Command
{
    protected $signature = 'clickhouse:migrate';

    protected $description = 'Create ClickHouse database and packet storage tables.';

    public function handle(ClickHouseClient $clickHouse): int
    {
        $database = config('ingestion.clickhouse.database');
        $table = config('ingestion.clickhouse.packets_table');

        $clickHouse->execute(sprintf(
            'CREATE DATABASE IF NOT EXISTS %s',
            $clickHouse->quoteIdentifier($database)
        ));

        $clickHouse->execute(sprintf(
            <<<'SQL'
CREATE TABLE IF NOT EXISTS %s.%s
(
    ingested_at DateTime64(3, 'UTC') DEFAULT now64(3),
    kafka_topic String,
    kafka_partition Int32,
    kafka_offset Int64,
    mqtt_topic String,
    device_identifier Nullable(String),
    payload_type LowCardinality(String),
    payload String,
    payload_json String,
    headers_json String
)
ENGINE = MergeTree
ORDER BY (mqtt_topic, ingested_at, kafka_partition, kafka_offset)
SQL,
            $clickHouse->quoteIdentifier($database),
            $clickHouse->quoteIdentifier($table)
        ));

        $this->info(sprintf('ClickHouse table is ready: %s.%s', $database, $table));

        return self::SUCCESS;
    }
}
