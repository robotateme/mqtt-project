<?php

declare(strict_types=1);

namespace Tests\Unit;

use Bus\Metrics\PrometheusMetricsRecorder;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

final class PrometheusMetricsRecorderTest extends TestCase
{
    public function test_renders_bus_metrics_in_prometheus_text_format(): void
    {
        $registry = new CollectorRegistry(new InMemory(), registerDefaultMetrics: false);
        $metrics = new PrometheusMetricsRecorder($registry, 'bus', 'bus-1', 'devices/+/telemetry');

        $metrics->setWorkerUp(true);
        $metrics->recordMqttMessage(0.012);
        $metrics->recordOutboxEnqueue(true);
        $metrics->recordKafkaPublish();
        $metrics->recordOutboxPublish();
        $metrics->setKafkaOutQueue(3);
        $metrics->setOutboxPending(1);

        $payload = (new RenderTextFormat())->render($registry->getMetricFamilySamples());

        self::assertStringContainsString('bus_worker_up{bus_id="bus-1"} 1', $payload);
        self::assertStringContainsString('bus_mqtt_messages_total{bus_id="bus-1",topic_filter="devices/+/telemetry"} 1', $payload);
        self::assertStringContainsString('bus_outbox_enqueues_total{bus_id="bus-1",result="stored"} 1', $payload);
        self::assertStringContainsString('bus_kafka_published_total{bus_id="bus-1"} 1', $payload);
        self::assertStringContainsString('bus_outbox_published_total{bus_id="bus-1"} 1', $payload);
        self::assertStringContainsString('bus_kafka_out_queue{bus_id="bus-1"} 3', $payload);
        self::assertStringContainsString('bus_outbox_pending{bus_id="bus-1"} 1', $payload);
        self::assertStringContainsString('bus_mqtt_processing_seconds_bucket', $payload);
    }
}
