<?php

namespace Camera;

class Logger
{
    public function log(string|array $message): void
    {
        error_log(json_encode(['date' => (new \DateTimeImmutable())->format(DATE_ATOM), 'message' => $message]));
    }
}
