<?php

namespace Camera\Repository;

use Camera\Stream;

class PersistenceRepositoryDecorator implements StreamRepositoryInterface
{

    private string $dir;

    public function __construct(private StreamRepositoryInterface $repository)
    {
        $this->dir = $_ENV['SNAPSHOT_DIR'] ?? '/tmp/resources/';
    }

    public function all(): array
    {
        $streams = $this->repository->all();
        $this->saveStreams($streams);
        return $streams;
    }

    /**
     * @param Stream[] $streams
     * @return void
     */
    private function saveStreams(array $streams): void
    {
        file_put_contents($this->dir . '/streams.json', json_encode($streams));
    }
}
