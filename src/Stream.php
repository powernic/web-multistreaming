<?php

namespace Camera;

final class Stream implements \JsonSerializable
{
    public function __construct(private string $id, private string $url)
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getName(): string
    {
        return $this->getId() . '-feed.ffm';
    }


    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url
        ];
    }
}
