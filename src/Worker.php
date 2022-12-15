<?php

namespace Camera;

use Camera\Message\JsonMessageSerializer;
use Camera\Message\UpdateStreamConfig;
use Camera\Service\ConfigService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Bridge\Redis\Transport\Connection;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisReceiver;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Worker as MessageWorker;
use Symfony\Component\Process\Process;

final class Worker
{

    private string $ffserverDSN;
    /**
     * @param ConfigService $configService
     * @param Process[] $streamProcesses
     * @param ?Process $ffServerProcess
     * @param string $ffServerHost
     */
    public function __construct(
        private Logger $logger,
        private ConfigService $configService,
        private int $ffServerPort,
        private string $ffServerHost = 'http://127.0.0.1',
        private array $streamProcesses = [],
        private ?Process $ffServerProcess = null)
    {
        $this->ffserverDSN = sprintf('%s:%d', $this->ffServerHost, $this->ffServerPort);
    }

    public function sigHandler(int $signo): void
    {
        switch ($signo) {
            case SIGTERM:
                $this->logger->log("Stopping process.");
                $this->stopFFStreams();
                $this->stopFFServer();
                $this->logger->log("Process stopped.");
                exit;
            case SIGHUP:
                $this->restartFF();
                break;
        }
    }

    public function run(): void
    {
        $this->bindSignals();
        $this->initFF();
        $this->runWorker();
    }

    private function bindSignals(): void
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, [$this, 'sigHandler']);
        pcntl_signal(SIGHUP, [$this, 'sigHandler']);
    }

    private function initFF(): void
    {
        $this->logger->log("Starting process.");
        $streams = $this->configService->getStreams();
        $this->startFFServer($streams);
        $this->runFFStreams($streams);
    }

    private function runWorker(): void
    {
        $bus = $this->createMessageBus();
        $receivers = $this->createReceivers();
        $eventDispatcher = $this->createEventDispatcher();
        $worker = new MessageWorker($receivers, $bus, $eventDispatcher);
        $worker->run();
    }

    private function createEventDispatcher(): EventDispatcher{
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(WorkerRunningEvent::class, function () {
            foreach ($this->streamProcesses as $streamProcess) {
                $streamProcess->isRunning();
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
                        function() {
                            $this->restartFF();
                        }
                    ]
                ]),
            )
        ]);
    }

    public function restartFF(): void
    {
        $this->logger->log('Starting update stream config');
        $streams = $this->configService->getStreams();
        $this->restartFFServer($streams);
        $this->restartFFStreams($streams);
        $this->logger->log('Finished update stream config');
    }

    /**
     * @param Stream[] $streams
     * @return void
     */
    private function restartFFStreams(array $streams): void
    {
        $this->stopFFStreams();
        $this->runFFStreams($streams);
    }

    /**
     * @param Stream[] $streams
     * @return void
     */
    private function runFFStreams(array $streams): void
    {
        foreach ($streams as $stream) {
            $feedUrl = $this->ffserverDSN . '/' . $stream->getName();
            $ffmpegProcess = new Process(
                [
                    "ffmpeg",
                    "-hide_banner",
                    "-loglevel",
                    "warning",
                    "-rtsp_transport",
                    "tcp",
                    "-i",
                    $stream->getUrl(),
                    $feedUrl
                ]
            );
            $this->logger->log('Starting stream: ' . $stream->getId());
            $this->streamProcesses[] = $ffmpegProcess;

            $ffmpegProcess->setPty(true);
            $ffmpegProcess->start(function ($type, $buffer) use ($stream) {
                $this->logger->log(['id' => $stream->getId(), 'message' => $buffer]);
            });
        }
    }

    private function stopFFStreams(): void
    {
        foreach ($this->streamProcesses as $process) {
            $process->stop();
        }
        $this->streamProcesses = [];
    }


    private function restartFFServer(array $streams): void
    {
        $this->stopFFServer();
        $this->startFFServer($streams);
    }

    private function startFFServer(array $streams): void
    {
        $this->configService->createFFServerConfig($streams);
        $this->ffServerProcess = new Process(["ffserver", "-hide_banner", "-loglevel", "warning"]);
        $this->ffServerProcess->start();
    }

    private function stopFFServer(): void
    {
        $this->ffServerProcess->stop();
    }

}
