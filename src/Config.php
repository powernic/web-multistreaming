<?php

namespace Camera;

final class Config
{

    /**
     * @param Stream[] $streams
     */
    public function __construct(
        private array $streams,
        private int $port,
        private string $address = '0.0.0.0',
        private string $feedPath = '/tmp',
        private string $size = '640x360',
        private string $snapshotStreamPostfix = 'still.jpg',
        private string $videoStreamPostfix = 'live.ogg',
        private string $snapshotFormat = 'jpeg',
        private string $videoFormat = 'ogg')
    {
    }


    public function getConfigContent(): string
    {
        $contentParts = [$this->getHeader()];
        foreach ($this->streams as $stream) {
            $contentParts[] = $this->getFeed($stream);
            $contentParts[] = $this->getVideoStream($stream);
            $contentParts[] = $this->getSnapshotStream($stream);
        }
        return implode(PHP_EOL, $contentParts);
    }

    private function getHeader(): string
    {
        return "HTTPPort $this->port
                HTTPBindAddress $this->address
                MaxHTTPConnections 2000
                MaxClients 1000
                MaxBandwidth 1000000
                <Stream status.html>
                Format status 
                </Stream>";
    }

    private function getFeed(Stream $stream): string
    {

        return sprintf(
            "<Feed %s>
                File %s/%s                
                FileMaxSize 50M
                </Feed>",
            $stream->getName(),
            $this->feedPath,
            $stream->getName()
        );
    }

    private function getSnapshotStream(Stream $stream): string
    {
        return $this->getStream($stream, $this->snapshotFormat, $this->snapshotStreamPostfix);
    }

    private function getVideoStream(Stream $stream): string
    {
        return $this->getStream($stream, $this->videoFormat, $this->videoStreamPostfix);
    }

    private function getStream(Stream $stream, string $format, string $name, int $delay = 0): string
    {

        return sprintf(
            "<Stream %s-%s>
                Feed %s
                Format %s 
                VideoFrameRate 15 
                VideoSize %s                
                VideoQMin 1
                VideoQMax 1
                VideoIntraOnly              
                VideoBitRate 4000
                NoAudio
                Strict -1 
                NoDefaults
                StartSendOnKey
                PreRoll %d
                </Stream>",
            $stream->getId(),
            $name,
            $stream->getName(),
            $format,
            $this->size,
            $delay
        );
    }
}
