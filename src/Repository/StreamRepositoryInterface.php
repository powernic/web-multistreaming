<?php

namespace Camera\Repository;

use Camera\Stream;

interface StreamRepositoryInterface
{
    /**
     * @return Stream[]
     */
    public function all(): array;
}
