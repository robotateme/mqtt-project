<?php

namespace App\Support\ClickHouse;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

final class ClickHouseClient
{
    public function __construct(
        private HttpFactory $http,
        private string $host,
        private int $port,
        private string $database,
        private string $username,
        private string $password,
    ) {
    }

    public function execute(string $sql): void
    {
        $response = $this->http
            ->withBasicAuth($this->username, $this->password)
            ->withBody($sql, 'text/plain')
            ->post($this->baseUrl());

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'ClickHouse query failed [%s]: %s',
                $response->status(),
                $response->body()
            ));
        }
    }

    public function insertJsonEachRow(string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $payload = sprintf(
            "INSERT INTO %s.%s FORMAT JSONEachRow\n%s",
            $this->quoteIdentifier($this->database),
            $this->quoteIdentifier($table),
            implode("\n", array_map(
                static fn (array $row): string => json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                $rows
            ))
        );

        $this->execute($payload);
    }

    private function baseUrl(): string
    {
        return sprintf('http://%s:%d/', $this->host, $this->port);
    }

    public function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }
}
