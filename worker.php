<?php
declare(strict_types=1);
declare(ticks=1);

use Camera\Logger;
use Camera\Repository\FileRepository;
use Camera\Repository\PersistenceRepositoryDecorator;
use Camera\Repository\RemoteFileRepository;
use Camera\Repository\RestApiRepository;
use Camera\Service\ParkingService;
use Camera\Worker;

require_once __DIR__ . '/vendor/autoload.php';
$logger = new Logger();
$type = $_ENV['TYPE'] ?: 'file';
$agentDSN = $_ENV['AGENT_DSN'] ?: 'http://parking.powernic.me/api/';
$agentToken =  $_ENV['API_KEY'] ?: '';
try {
    switch ($type) {
        case 'file':
            $streamRepository = new FileRepository($_ENV['CONFIG']);
            break;
        case 'remote_file':
            $streamRepository = new RemoteFileRepository($_ENV['CONFIG']);
            break;
        case 'rest':
            $streamRepository = new RestApiRepository($agentDSN);
            break;
        default:
            throw new \Exception('Invalid ' . $_ENV['TYPE'] . ' type');
    }
    $worker = new Worker(
        logger: $logger,
        parkingService: new ParkingService($agentDSN, $agentToken),
        streamRepository: new PersistenceRepositoryDecorator($streamRepository)
    );
    $worker->run();
} catch (\Exception $e) {
    $logger->log($e->getMessage());
}
