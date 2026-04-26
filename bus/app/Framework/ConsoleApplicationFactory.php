<?php

declare(strict_types=1);

namespace Bus\Framework;

use Bus\Console\ConsumeMqttCommand;
use Symfony\Component\Console\Application;

final readonly class ConsoleApplicationFactory
{
    public static function create(ConsumeMqttCommand $consumeMqttCommand): Application
    {
        $application = new Application('bus');
        $application->addCommand($consumeMqttCommand);
        $application->setDefaultCommand('mqtt:consume');

        return $application;
    }
}
