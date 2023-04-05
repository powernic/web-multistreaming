<?php

namespace Camera\Repository;

use Camera\Stream;
use GuzzleHttp\Client;

class RemoteFileRepository extends StreamRepository
{

    private Client $client;

    public function __construct(private string $configFilePath)
    {
        $this->client = new Client();
    }

    public function all(): array
    {
        $streamBody = $this->client->get($this->configFilePath)->getBody()->getContents();
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
