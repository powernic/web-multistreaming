<?php
declare(strict_types=1);
declare(ticks=1);

use Camera\Logger;
use Camera\Service\ConfigService;
use Camera\Worker;

require_once __DIR__ . '/vendor/autoload.php';

$logger = new Logger();
$ffServerPort = $_ENV['FFSERVER_PORT'] ? (int)$_ENV['FFSERVER_PORT'] : 8090;
try {
    $configService = new ConfigService(ffServerPort: $ffServerPort);
    $worker = new Worker(logger: $logger, configService: $configService, ffServerPort: $ffServerPort);
    $worker->run();
} catch (\Exception $e) {
    $logger->log($e->getMessage());
}
