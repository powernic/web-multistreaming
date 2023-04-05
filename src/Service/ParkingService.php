<?php

namespace Camera\Service;

use Camera\StreamProcess;
use GuzzleHttp\Client;

class ParkingService
{
    private Client $client;

    public function __construct(
        private string $dsn,
        private string $token)
    {
        $this->client = new Client([
            'base_uri' => $this->dsn,
        ]);
    }

    public function uploadSnapshot(string $parkingId, string $snapshotPath): void
    {
        $this->client->post('agent/' . $this->token . '/snapshot/' . $parkingId, [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($snapshotPath, 'r'),
                    'filename' => $parkingId . '.jpg'
                ]
            ]
        ]);
    }
}
