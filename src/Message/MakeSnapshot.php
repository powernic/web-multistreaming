<?php

namespace Camera\Message;

class MakeSnapshot
{
    public function __construct(private string $id)
    {
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }


}
