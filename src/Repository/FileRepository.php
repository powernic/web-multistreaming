<?php

namespace Camera\Repository;

use Camera\Stream;

class FileRepository extends StreamRepository
{
    public function __construct(private string $configFilePath = '/app/config.json', private bool $isRemote = false)
    {
    }

    public function all(): array
    {
        if (!$this->isRemote && !file_exists($this->configFilePath)) {
            throw new \Exception('Stream File ' . $this->configFilePath . ' not found');
        }
        $streamBody = file_get_contents($this->configFilePath);
        if (!empty($streamBody)) {
            $streamConfig = json_decode($streamBody, true);
        } else {
            throw new \Exception('Could not get stream config from ' . $this->configFilePath);
        }
        return array_map(function ($streamData) {
            return new Stream($streamData['id'], $streamData['url']);
        }, $streamConfig);
    }

}
