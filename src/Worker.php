<?php

namespace Camera;

use Camera\Message\JsonMessageSerializer;
use Camera\Message\UpdateStreamConfig;
use Camera\Repository\StreamRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Bridge\Redis\Transport\Connection;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisReceiver;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Worker as MessageWorker;

final class Worker
{

    private StreamProcessCollection $streamProcesses;

    /**
     * @param Logger $logger
     * @param StreamRepositoryInterface $streamRepository
     */
    public function __construct(
        private readonly Logger $logger,
        private readonly StreamRepositoryInterface $streamRepository)
    {
        $this->streamProcesses = new StreamProcessCollection();
    }

    public function sigHandler(int $signo): void
    {
        switch ($signo) {
            case SIGTERM:
                $this->logger->log("Stopping process.");
                $this->streamProcesses->stopAll();
                $this->logger->log("Process stopped.");
                exit;
            case SIGHUP:
                $this->pullAndUpdateStreams();
                break;
        }
    }

    public function run(): void
    {
        $this->bindSignals();
        $this->initStreams();
        $this->runWorker();
    }

    private function bindSignals(): void
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, [$this, 'sigHandler']);
        pcntl_signal(SIGHUP, [$this, 'sigHandler']);
    }

    private function initStreams(): void
    {
        $this->logger->log("Starting process.");
        $streams = $this->streamRepository->all();
        $this->runVideoStreams($streams);
    }

    private function runWorker(): void
    {
        $bus = $this->createMessageBus();
        $receivers = $this->createReceivers();
        $eventDispatcher = $this->createEventDispatcher();
        $worker = new MessageWorker($receivers, $bus, $eventDispatcher);
        $worker->run();
    }

    private function createEventDispatcher(): EventDispatcher
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(WorkerRunningEvent::class, function () {
            foreach ($this->streamProcesses->getAll() as $streamProcess) {
                $running = $streamProcess->isRunning();
                if (!$running) {
                    $this->logger->log("Stream {$streamProcess->getId()} is not running. Restarting.");
                    $streamProcess->retry();
                    if($streamProcess->isRunning()) {
                        $this->logger->log("Restored running stream {$streamProcess->getId()}");
                    }
                }
            }
        });
        return $eventDispatcher;
    }

    private function createReceivers(): array
    {
        $connection = Connection::fromDsn($_ENV['MESSENGER_TRANSPORT_DSN']);
        return [
            new RedisReceiver($connection, new JsonMessageSerializer()),
        ];
    }

    private function createMessageBus(): MessageBus
    {
        return new MessageBus([
            new HandleMessageMiddleware(
                new HandlersLocator([
                    UpdateStreamConfig::class => [
                        function () {
                            $this->pullAndUpdateStreams();
                        }
                    ]
                ]),
            )
        ]);
    }

    public function pullAndUpdateStreams(): void
    {
        $this->logger->log('Starting update stream config');
        $streams = $this->streamRepository->all();
        $this->updateStreams($streams);
        $this->logger->log('Finished update stream config');
    }

    /**
     * @param Stream[] $streams
     * @return void
     */
    private function updateStreams(array $streams): void
    {
        $unusedIds = $this->findUnusedStreamIds($streams);
        if (!empty($unusedIds)) {
            $this->stopStream($unusedIds);
        }
        $newStreams = $this->findNewStreams($streams);
        $this->runVideoStreams($newStreams);
    }

    /**
     * @param Stream[] $streams
     * @return Stream[]
     */
    private function findNewStreams(array $streams): array
    {
        return array_filter($streams, function (Stream $stream) {
            return !$this->streamProcesses->isRunning($stream->getId());
        });
    }

    /**
     * @param Stream[] $streams
     * @return void
     */
    private function runVideoStreams(array $streams): void
    {
        foreach ($streams as $stream) {
            $streamProcess = new StreamProcess($stream);
            $this->streamProcesses->add($streamProcess);
            $streamProcess->setPty(true);
            $streamProcess->start(function ($type, $buffer) use ($stream) {
                $this->logger->log(['id' => $stream->getId(), 'type' => $type, 'message' => $buffer]);
            });
            $this->logger->log('Stream started: ' . $stream->getId());
        }
    }

    /**
     * @param Stream[] $streams
     * @return string[]
     */
    private function findUnusedStreamIds(array $streams): array
    {
        $streamIds = array_map(function (Stream $stream) {
            return $stream->getId();
        }, $streams);
        return array_diff($this->streamProcesses->getRunningStreamIds(), $streamIds);
    }

    /**
     * @param string[] $streamIds
     * @return void
     */
    private function stopStream(array $streamIds): void
    {
        $this->streamProcesses->stopByIds($streamIds, function (string $id) {
            $this->logger->log('Stream stopped: ' . $id);
        });
    }


}
