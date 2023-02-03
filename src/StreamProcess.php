<?php

namespace Camera;

use Symfony\Component\Process\Process;

class StreamProcess
{

    private Stream $stream;
    private Process $snapshotProcess;
    private Process $videoProcess;
    private int $maxRetryDelay = 10;
    private string $streamHost;
    private $snapshotDir;

    public function __construct(Stream $stream)
    {
        $this->snapshotDir = $_ENV['SNAPSHOT_DIR'] ?? '/tmp/resources/';
        $this->streamHost = $_ENV['STREAM_HOST'] ?? '';
        $this->stream = $stream;

        $this->snapshotProcess = Process::fromShellCommandline($this->getSnapshotCommand($stream));
        $this->videoProcess = new Process($this->getVideoCommand($stream));
    }

    private function getSnapshotCommand(Stream $stream): string
    {
        return
            'ffmpeg -y -loglevel error -rtsp_transport tcp -i ' . $stream->getUrl(
            ) . ' -update 1 -vf fps=15,scale="640:360" -qscale:v 1 -vsync 0 ' . $this->snapshotDir . '/' . $stream->getId(
            ) . '.jpg';
    }

    private function getVideoCommand(Stream $stream): array
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

    public function getCommandLines(): array
    {
        return [$this->snapshotProcess->getCommandLine(), $this->videoProcess->getCommandLine()];
    }

    public function isRunning(): bool
    {
        return $this->snapshotProcess->isRunning() && $this->videoProcess->isRunning();
    }

    public function getOutput(): string
    {
        return $this->snapshotProcess->getOutput() . $this->videoProcess->getOutput();
    }

    public function setPty(bool $bool): self
    {
        $this->snapshotProcess->setPty($bool);
        $this->videoProcess->setPty($bool);
        return $this;
    }

    public function start(callable $callback = null, array $env = []): void
    {
        $this->snapshotProcess->start($callback, $env);
        $this->videoProcess->start($callback, $env);
    }

    /**
     * @param float $timeout
     * @param int|null $signal
     * @return array<int,int|null>
     */
    public function stop(float $timeout = 10, int $signal = null): array
    {
        return [
            $this->snapshotProcess->stop($timeout, $signal),
            $this->videoProcess->stop($timeout, $signal)
        ];
    }

    public function restart(): void
    {
        $this->snapshotProcess = $this->snapshotProcess->restart();
        $this->videoProcess = $this->videoProcess->restart();
    }

    public function retry(): void
    {
        $this->stop();
        $this->restart();
        sleep($this->maxRetryDelay);
    }


}
