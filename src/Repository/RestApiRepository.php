<?php

namespace Camera\Repository;

use Camera\Stream;

class RestApiRepository extends StreamRepository
{
    private ?string $token = null;

    public function __construct(
        private string $streamEndpoint = 'http://http/api/streams',
        private string $authEndpoint = 'http://http/api/auth')
    {
    }

    private function getToken(): string
    {
        if ($this->token === null) {
            $opts = [
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-type: application/json',
                    'content' => json_encode([
                        "username" => $_ENV['API_USERNAME'],
                        "password" => $_ENV['API_PASSWORD']
                    ])
                ]
            ];
            $context = stream_context_create($opts);
            $auth = @file_get_contents($this->authEndpoint, false, $context);
            $response = json_decode($auth, true);
            $this->token = $response['token'];
        }
        return $this->token;
    }

    /**
     * @return Stream[]
     * @throws \Exception
     */
    public function all(): array
    {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Bearer ' . $this->getToken()
            ]
        ];
        $context = stream_context_create($opts);
        $streamBody = @file_get_contents($this->streamEndpoint, false, $context);
        if (!empty($streamBody)) {
            $streamConfig = json_decode($streamBody, true);
        } else {
            throw new \Exception('Could not get stream config from API');
        }
        return array_map(function ($streamData) {
            return new Stream($streamData['id'], $streamData['url']);
        }, $streamConfig);
    }
}
