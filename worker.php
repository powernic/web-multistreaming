<?php
declare(strict_types=1);
declare(ticks=1);

use Camera\Logger;
use Camera\Repository\FileRepository;
use Camera\Repository\RestApiRepository;
use Camera\Worker;

require_once __DIR__ . '/vendor/autoload.php';
$logger = new Logger();
$type = $_ENV['TYPE'] ?: 'file';
try {
    switch ($type) {
        case 'file':
            $streamRepository = new FileRepository($_ENV['CONFIG']);
            break;
        case 'remote_file':
            $streamRepository = new FileRepository($_ENV['CONFIG'], true);
            break;
        case 'rest':
            $streamRepository = new RestApiRepository();
            break;
        default:
            throw new \Exception('Invalid ' . $_ENV['TYPE'] . ' type');
    }
    $worker = new Worker(
        logger: $logger,
        streamRepository: $streamRepository
    );
    $worker->run();
} catch (\Exception $e) {
    $logger->log($e->getMessage());
}
