<?php

declare(strict_types=1);

namespace Bus\Framework;

use Bus\Config\Loader\ConfigLoader;
use Bus\Config\Value\BusConfig;
use Bus\Console\ConsumeMqttCommand;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final readonly class ContainerFactory
{
    public static function build(string $basePath): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('bus.base_path', $basePath);

        $config = ConfigLoader::load($basePath);
        $consumeMqttCommand = new ConsumeMqttCommand($config);

        $container->set(BusConfig::class, $config);
        $container->set(ConsumeMqttCommand::class, $consumeMqttCommand);
        $container->set(Application::class, ConsoleApplicationFactory::create($consumeMqttCommand));

        $container->compile();

        return $container;
    }

    public static function application(string $basePath): Application
    {
        $application = self::build($basePath)->get(Application::class);

        if (!$application instanceof Application) {
            throw new RuntimeException('Bus console application service is not registered.');
        }

        return $application;
    }
}
