<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Kafka\KafkaPacketMapper;
use App\Support\Packets\PacketRealtimePublisher;
use Core\Application\Packets\PacketStoragePort;
use Illuminate\Console\Command;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RuntimeException;
use Throwable;

final class ConsumeKafkaPackets extends Command
{
    protected $signature = 'kafka:consume-packets
        {--once : Stop after one successful batch}
        {--max-messages=0 : Stop after consuming this many messages; 0 means unlimited}';

    protected $description = 'Consume MQTT packet events from Kafka and store them in ClickHouse.';

    public function handle(
        PacketStoragePort $packetStorage,
        KafkaPacketMapper $mapper,
        PacketRealtimePublisher $realtime,
    ): int {
        $consumer = $this->consumer();
        $topic = config('ingestion.kafka.packet_topic');
        $batchSize = config('ingestion.kafka.batch_size');
        $timeoutMs = config('ingestion.kafka.consume_timeout_ms');
        $maxMessages = (int) $this->option('max-messages');
        $consumed = 0;
        /** @var array<int, array<string, mixed>> $batch */
        $batch = [];
        /** @var array<int, Message> $messages */
        $messages = [];

        $consumer->subscribe([$topic]);
        $this->info(sprintf('Consuming Kafka topic [%s].', $topic));

        while (true) {
            $message = $consumer->consume($timeoutMs);

            if ($message->err === RD_KAFKA_RESP_ERR__TIMED_OUT) {
                $this->flushBatch($packetStorage, $realtime, $consumer, $batch, $messages);
                continue;
            }

            if ($message->err !== RD_KAFKA_RESP_ERR_NO_ERROR) {
                throw new RuntimeException($message->errstr(), $message->err);
            }

            /** @var array<string, mixed> $row */
            $row = $this->row($message, $mapper);
            $batch[] = $row;
            $messages[] = $message;
            $consumed++;

            if (count($batch) >= $batchSize) {
                $this->flushBatch($packetStorage, $realtime, $consumer, $batch, $messages);

                if ($this->option('once')) {
                    return self::SUCCESS;
                }
            }

            if ($maxMessages > 0 && $consumed >= $maxMessages) {
                $this->flushBatch($packetStorage, $realtime, $consumer, $batch, $messages);

                return self::SUCCESS;
            }
        }
    }

    private function consumer(): KafkaConsumer
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', config('ingestion.kafka.brokers'));
        $conf->set('group.id', config('ingestion.kafka.group_id'));
        $conf->set('auto.offset.reset', config('ingestion.kafka.offset_reset'));
        $conf->set('enable.auto.commit', 'false');

        return new KafkaConsumer($conf);
    }

    private function row(Message $message, KafkaPacketMapper $mapper): array
    {
        return $mapper->map(
            $message->topic_name,
            $message->partition,
            $message->offset,
            (string) $message->key,
            (string) $message->payload,
            $message->headers ?? [],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $batch
     * @param array<int, Message> $messages
     */
    private function flushBatch(
        PacketStoragePort $packetStorage,
        PacketRealtimePublisher $realtime,
        KafkaConsumer $consumer,
        array &$batch,
        array &$messages,
    ): void {
        if ($batch === []) {
            return;
        }

        $packetStorage->store($batch);

        foreach ($batch as $packet) {
            try {
                $realtime->publish($packet);
            } catch (Throwable $exception) {
                $this->warn(sprintf('Realtime publish skipped: %s', $exception->getMessage()));
            }
        }

        foreach ($messages as $message) {
            $consumer->commit($message);
        }

        $this->line(sprintf('Stored %d packet(s).', count($batch)));

        $batch = [];
        $messages = [];
    }
}
