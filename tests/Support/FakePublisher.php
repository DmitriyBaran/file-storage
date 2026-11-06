<?php

namespace Tests\Support;

use App\Messaging\Contracts\MessagePublisher;

class FakePublisher implements MessagePublisher
{
    /** @var list<array{queue: string, payload: array}> */
    public array $published = [];

    public function publish(string $queue, array $payload): void
    {
        $this->published[] = ['queue' => $queue, 'payload' => $payload];
    }
}
