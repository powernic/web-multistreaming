<?php

namespace Camera\Message\Handler;

use Camera\Logger;
use Camera\Message\MakeSnapshot;
use Camera\Service\ParkingService;
use Camera\StreamProcessCollection;

class MakeSnapshotHandler
{
    public function __construct(
        private readonly Logger $logger,
        private readonly ParkingService $parkingService,
        private StreamProcessCollection $streamProcesses)
    {
    }

    public function __invoke(MakeSnapshot $message): void
    {
        $this->logger->log("Received message snapshot.make for {$message->getId()}");
        $found = false;
        foreach ($this->streamProcesses->getAll() as $streamProcess) {
            if ($streamProcess->getId() === $message->getId()) {
                try {
                    $this->parkingService->uploadSnapshot($streamProcess->getId(), $streamProcess->getSnapshotPath());
                }catch (\Exception $e) {
                    $this->logger->log("Snapshot for {$message->getId()} upload failed: {$e->getMessage()}");
                    return;
                }
                $this->logger->log("Snapshot for {$message->getId()} uploaded");
                $found = true;
            }
        }
        if (!$found) {
            $this->logger->log("Snapshot for {$message->getId()} not found");
        }
    }
}
