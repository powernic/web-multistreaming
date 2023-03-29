<?php

namespace Camera;

use Symfony\Component\Process\Process;

class StreamProcess
{

    private Stream $stream;
    private Process $parallelProcess;
    private int $maxRetryDelay = 10;
    private string $streamHost;
    private $snapshotDir;

    public function __construct(Stream $stream)
    {
        $this->snapshotDir = $_ENV['SNAPSHOT_DIR'] ?? '/tmp/resources/';
        $this->streamHost = $_ENV['STREAM_HOST'] ?? '';
        $this->stream = $stream;

        $this->parallelProcess = new Process(
            array_merge(
                $this->getInputCommand($stream),
                $this->getSnapshotCommand($stream),
                $this->getVideoCommand($stream)
            )
        );
    }

    private function getInputCommand(Stream $stream): array
    {
        return [
            "ffmpeg",
            "-y",
            "-loglevel",
            "error",
            "-rtsp_transport",
            "tcp",
            "-i",
            $stream->getUrl(),
        ];
    }

    private function getSnapshotCommand(Stream $stream): array
    {
        return [
            "-update",
            "1",
            "-vf",
            "fps=15,scale=640:360",
            "-qscale:v",
            "1",
            "-vsync",
            "0",
            $this->snapshotDir . '/' . $stream->getId() . '.jpg'
        ];
    }

    private function getVideoCommand(Stream $stream): array
    {
        return [
            "-c",
            "copy",
            "-f",
            "flv",
            "rtmp://{$this->streamHost}/live/{$stream->getId()}"
        ];
    }

    public function getId(): string
    {
        return $this->stream->getId();
    }

    public function isRunning(): bool
    {
        return $this->parallelProcess->isRunning();
    }

    public function getOutput(): string
    {
        return $this->parallelProcess->getOutput();
    }

    public function setPty(bool $bool): self
    {
        $this->parallelProcess->setPty($bool);
        return $this;
    }

    public function start(callable $callback = null, array $env = []): void
    {
        $this->parallelProcess->start($callback, $env);
    }

    /**
     * @param float $timeout
     * @param int|null $signal
     * @return int|null
     */
    public function stop(float $timeout = 10, int $signal = null): ?int
    {
        return $this->parallelProcess->stop($timeout, $signal);
    }

    public function restart(callable $callback = null): void
    {
        $this->parallelProcess = $this->parallelProcess->restart($callback);
    }

    public function retry(callable $callback = null): void
    {
        $this->stop();
        $this->restart($callback);
        sleep($this->maxRetryDelay);
    }


}
