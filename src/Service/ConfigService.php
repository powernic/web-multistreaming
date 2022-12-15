<?php

namespace Camera\Service;

use Camera\Config;
use Camera\Stream;

class ConfigService
{
    private ?string $token = null;

    public function __construct(
        private int $ffServerPort,
        private string $streamApi = 'http://http/api/streams',
        private string $authApi = 'http://http/api/auth',
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

    private function getToken(): string
    {
        if ($this->token === null) {
            $opts = [
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-type: application/json',
                    'content' => json_encode([
                        "username" => $_ENV['CAMERA_USERNAME'],
                        "password" => $_ENV['CAMERA_PASSWORD']
                    ])
                ]
            ];
            $context = stream_context_create($opts);
            $auth = @file_get_contents($this->authApi, false, $context);
            $response = json_decode($auth, true);
            $this->token = $response['token'];
        }
        return $this->token;
    }

    /**
     * @return Stream[]
     * @throws \Exception
     */
    public function getStreams(): array
    {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Bearer ' . $this->getToken()
            ]
        ];
        $context = stream_context_create($opts);
        $streamBody = @file_get_contents($this->streamApi, false, $context);
        if (!empty($streamBody)) {
            $streamConfig = json_decode($streamBody, true);
        } else {
            throw new \Exception('Could not get stream config from API');
        }
        return array_map(function ($streamData) {
            return new Stream($streamData['id'], $streamData['url']);
        }, $streamConfig);
    }


    private function saveFFServerConfig(Config $config): void
    {
        file_put_contents($this->ffServerFile, $config->getConfigContent());
    }
}
