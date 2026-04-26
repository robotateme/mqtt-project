<?php

declare(strict_types=1);

namespace Bus\Console;

use Bus\Config\Value\BusConfig;
use Bus\Mqtt\MqttWorkerFactory;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mqtt:consume', description: 'Consume MQTT packets and publish them to Kafka.')]
final class ConsumeMqttCommand extends Command
{
    public function __construct(private readonly BusConfig $config)
    {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        MqttWorkerFactory::fromConfig($this->config)->run();

        return Command::SUCCESS;
    }
}
