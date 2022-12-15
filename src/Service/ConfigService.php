<?php

namespace Camera\Service;

use Camera\Config;
use Camera\Stream;

class ConfigService
{

    public function __construct(
        private int $ffServerPort,
        private string $ffServerFile = '/etc/ffserver.conf')
    {
    }

    /**
     * @param Stream[] $streams
     * @return void
     */
    public function createFFServerConfig(array $streams): void
    {
        $config = new Config(streams: $streams, port: $this->ffServerPort);
        $this->saveFFServerConfig($config);
    }

    private function saveFFServerConfig(Config $config): void
    {
        file_put_contents($this->ffServerFile, $config->getConfigContent());
    }
}
