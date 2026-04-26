<?php

declare(strict_types=1);

use Bus\Config\ConfigLoader;
use Bus\Mqtt\MqttWorkerFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

MqttWorkerFactory::fromConfig(ConfigLoader::load(dirname(__DIR__)))->run();
