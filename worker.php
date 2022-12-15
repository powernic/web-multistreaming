<?php
declare(strict_types=1);
declare(ticks=1);

use Camera\Logger;
use Camera\Repository\FileRepository;
use Camera\Repository\RestApiRepository;
use Camera\Service\ConfigService;
use Camera\Worker;

require_once __DIR__ . '/vendor/autoload.php';
$logger = new Logger();
$ffServerPort = isset($_ENV['FFSERVER_PORT']) ? (int)$_ENV['FFSERVER_PORT'] : 80;
$type = $_ENV['TYPE'] ?: 'file';
try {
    $configService = new ConfigService(ffServerPort: $ffServerPort);
    switch ($type) {
        case 'file':
            $streamRepository = new FileRepository($_ENV['CONFIG']);
            break;
        case 'rest':
            $streamRepository = new RestApiRepository();
            break;
        default:
            throw new \Exception('Invalid ' . $_ENV['TYPE'] . ' type');
    }
    $worker = new Worker(
        logger: $logger,
        configService: $configService,
        ffServerPort: $ffServerPort,
        streamRepository: $streamRepository
    );
    $worker->run();
} catch (\Exception $e) {
    $logger->log($e->getMessage());
}
