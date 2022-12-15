<?php

namespace Camera\Message;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class JsonMessageSerializer implements SerializerInterface
{

    /**
     * @inheritDoc
     */
    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'];
        $headers = $encodedEnvelope['headers'];
        $data = json_decode($body, true);

        if (null === $data) {
            throw new MessageDecodingFailedException('Invalid JSON');
        }
        if (!isset($headers['type'])) {
            throw new MessageDecodingFailedException('Missing "type" header');
        }

        switch ($headers['type']) {
            case 'config.stream.update':
                $envelope = $this->createCommandEnvelope($data);
                break;
            default:
                throw new MessageDecodingFailedException(sprintf('Invalid type "%s"', $headers['type']));
        }
        $stamps = [];
        if (isset($headers['stamps'])) {
            $stamps = unserialize($headers['stamps']);
        }
        return $envelope->with(... $stamps);
    }

    private function createCommandEnvelope($data): Envelope
    {
        $message = new UpdateStreamConfig();
        $envelope = new Envelope($message);
        return $envelope->with(new BusNameStamp('command.bus'));
    }
    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function encode(Envelope $envelope): array
    {
        throw new \Exception('Transport & serializer not meant for sending messages');
    }
}
