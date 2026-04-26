<?php

declare(strict_types=1);

namespace Bus\Mqtt;

use Bus\Contracts\MqttClientPort;
use Bus\Contracts\OutboxStorePort;
use Bus\Outbox\OutboxPublisher;
use Bus\Runtime\RuntimeStatus;
use PhpMqtt\Client\ConnectionSettings;

final class MqttWorker
{
    private string $startedAt;
    private int $receivedMessages = 0;
    private int $enqueuedMessages = 0;
    private int $publishedFromOutbox = 0;
    private bool $stopped = false;

    public function __construct(
        private MqttClientPort $mqtt,
        private ConnectionSettings $settings,
        private bool $cleanSession,
        private string $topic,
        private int $qualityOfService,
        private OutboxStorePort $outbox,
        private OutboxPublisher $outboxPublisher,
        private RuntimeStatus $status,
    ) {
        $this->startedAt = gmdate('c');
    }

    public function run(): void
    {
        $this->registerShutdownHandler();
        $this->registerSignalHandlers();

        $this->mqtt->connect($this->settings, $this->cleanSession);
        $this->writeStatus('running', true);

        $this->publishedFromOutbox += $this->outboxPublisher->drain();

        $this->mqtt->subscribe($this->topic, $this->handleMqttMessage(...), $this->qualityOfService);
        $this->mqtt->loop(true);
    }

    public function consume(string $topic, string $message): void
    {
        $this->receivedMessages++;

        if ($this->outbox->enqueue($topic, $message) !== null) {
            $this->enqueuedMessages++;
        }

        $this->publishedFromOutbox += $this->outboxPublisher->drain();
        $this->writeStatus('running');
    }

    /**
     * @param array<string, string> $matchedWildcards
     */
    private function handleMqttMessage(string $topic, string $message, bool $retained, array $matchedWildcards): void
    {
        unset($retained, $matchedWildcards);

        $this->consume($topic, $message);
    }

    public function stop(): void
    {
        if ($this->stopped) {
            return;
        }

        $this->stopped = true;
        $this->outboxPublisher->flush();
        $this->writeStatus('stopped', true);
    }

    private function registerShutdownHandler(): void
    {
        register_shutdown_function($this->stop(...));
    }

    private function registerSignalHandlers(): void
    {
        if (!function_exists('pcntl_async_signals')) {
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, static fn () => exit(0));
        pcntl_signal(SIGINT, static fn () => exit(0));
    }

    private function writeStatus(string $status, bool $force = false): void
    {
        $this->status->write([
            'status' => $status,
            'started_at' => $this->startedAt,
            'received_messages' => $this->receivedMessages,
            'enqueued_messages' => $this->enqueuedMessages,
            'published_from_outbox' => $this->publishedFromOutbox,
            'outbox' => $this->outboxPublisher->stats(),
        ], $force);
    }
}
