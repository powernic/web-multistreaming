<?php

namespace Camera\Repository;

use Camera\Stream;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class RestApiRepository extends StreamRepository
{
    private ?string $token = null;
    private ?string $refreshToken = null;
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'http://http/api/',
        ]);
    }

    private function getToken(): string
    {
        if ($this->token === null) {
            $response = $this->client->post('auth', [
                'json' => [
                    'username' => $_ENV['API_USERNAME'],
                    "password" => $_ENV['API_PASSWORD']
                ]
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            $this->token = $data['token'];
            $this->refreshToken = $data['refresh_token'];
        }
        return $this->token;
    }

    private function updateToken(): void
    {
        $response = $this->client->post('auth/refresh', [
            'json' => [
                'refresh_token' => $this->refreshToken,
            ]
        ]);
        $data = json_decode($response->getBody()->getContents(), true);
        $this->token = $data['token'];
        $this->refreshToken = $data['refresh_token'];
    }

    /**
     * @return Stream[]
     * @throws \Exception
     */
    public function all(): array
    {
        try {
            $response = $this->client->get('streams', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->getToken(),
                ]
            ]);
        } catch (ClientException $e) {
            if ($e->getCode() !== 401)
                throw $e;
            $content = $e->getResponse()->getBody()->getContents();
            $error = json_decode($content, true);
            if ($error['message'] !== 'Expired JWT Token') {
                throw new \Exception($error['message']);
            }
            $this->updateToken();
            return $this->all();
        }
        $streamBody = $response->getBody()->getContents();
        $streamConfig = json_decode($streamBody, true);
        return array_map(function ($streamData) {
            return new Stream($streamData['id'], $streamData['url']);
        }, $streamConfig);
    }
}
