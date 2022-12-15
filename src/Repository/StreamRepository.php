<?php

namespace Camera\Repository;

abstract class StreamRepository implements StreamRepositoryInterface
{

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return [];
    }
}
