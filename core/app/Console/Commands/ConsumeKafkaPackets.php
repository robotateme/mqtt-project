<?php

namespace App\Console\Commands;

use App\Support\ClickHouse\ClickHouseClient;
use App\Support\Packets\PacketInterpreter;
use Illuminate\Console\Command;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RuntimeException;

class ConsumeKafkaPackets extends Command
{
    protected $signature = 'kafka:consume-packets
        {--once : Stop after one successful batch}
        {--max-messages=0 : Stop after consuming this many messages; 0 means unlimited}';

    protected $description = 'Consume MQTT packet events from Kafka and store them in ClickHouse.';

    public function handle(ClickHouseClient $clickHouse, PacketInterpreter $interpreter): int
    {
        $consumer = $this->consumer();
        $topic = config('ingestion.kafka.packet_topic');
        $batchSize = config('ingestion.kafka.batch_size');
        $timeoutMs = config('ingestion.kafka.consume_timeout_ms');
        $maxMessages = (int) $this->option('max-messages');
        $consumed = 0;
        $batch = [];
        $messages = [];

        $consumer->subscribe([$topic]);
        $this->info(sprintf('Consuming Kafka topic [%s].', $topic));

        while (true) {
            $message = $consumer->consume($timeoutMs);

            if ($message->err === RD_KAFKA_RESP_ERR__TIMED_OUT) {
                $this->flushBatch($clickHouse, $consumer, $batch, $messages);
                continue;
            }

            if ($message->err !== RD_KAFKA_RESP_ERR_NO_ERROR) {
                throw new RuntimeException($message->errstr(), $message->err);
            }

            $batch[] = $this->row($message, $interpreter);
            $messages[] = $message;
            $consumed++;

            if (count($batch) >= $batchSize) {
                $this->flushBatch($clickHouse, $consumer, $batch, $messages);

                if ($this->option('once')) {
                    return self::SUCCESS;
                }
            }

            if ($maxMessages > 0 && $consumed >= $maxMessages) {
                $this->flushBatch($clickHouse, $consumer, $batch, $messages);

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

    private function row(Message $message, PacketInterpreter $interpreter): array
    {
        $mqttTopic = (string) $message->key;
        $payload = (string) $message->payload;
        $interpreted = $interpreter->interpret($mqttTopic, $payload);

        return [
            'kafka_topic' => $message->topic_name,
            'kafka_partition' => $message->partition,
            'kafka_offset' => $message->offset,
            'mqtt_topic' => $mqttTopic,
            'device_identifier' => $interpreted['device_identifier'],
            'payload_type' => $interpreted['payload_type'],
            'payload' => $payload,
            'payload_json' => json_encode($interpreted['payload_json'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'headers_json' => json_encode($message->headers ?? [], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        ];
    }

    private function flushBatch(ClickHouseClient $clickHouse, KafkaConsumer $consumer, array &$batch, array &$messages): void
    {
        if ($batch === []) {
            return;
        }

        $clickHouse->insertJsonEachRow(config('ingestion.clickhouse.packets_table'), $batch);

        foreach ($messages as $message) {
            $consumer->commit($message);
        }

        $this->line(sprintf('Stored %d packet(s).', count($batch)));

        $batch = [];
        $messages = [];
    }
}
