<?php

declare(strict_types=1);

namespace Bus\Config\Value;

final readonly class KafkaConfig
{
    public function __construct(
        public string $brokers,
        public string $topic,
        public int $batchSize,
        public int $lingerMs,
        public int $maxOutstanding,
        public int $backpressureTimeoutMs,
        public int $messageTimeoutMs,
    ) {
    }
}
