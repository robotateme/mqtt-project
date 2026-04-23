<?php

declare(strict_types=1);

namespace App\Support\Mercure;

use Firebase\JWT\JWT;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use RuntimeException;

final readonly class MercureClient
{
    public function __construct(
        private HttpFactory $http,
        private string $publishUrl,
        private string $publisherJwtKey,
        private string $jwtAlgorithm,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @psalm-suppress PossiblyUnusedMethod Used by application services to publish realtime events.
     */
    public function publish(string $topic, array $data, bool $private = false): void
    {
        $payload = [
            'topic' => $topic,
            'data' => json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        ];

        if ($private) {
            $payload['private'] = 'on';
        }

        /** @var Response $response */
        $response = $this->http
            ->asForm()
            ->withToken($this->publisherToken())
            ->post($this->publishUrl, $payload);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Mercure publish failed [%s]: %s',
                $response->status(),
                $response->body()
            ));
        }
    }

    private function publisherToken(): string
    {
        return JWT::encode([
            'mercure' => [
                'publish' => ['*'],
            ],
        ], $this->publisherJwtKey, $this->jwtAlgorithm);
    }
}
