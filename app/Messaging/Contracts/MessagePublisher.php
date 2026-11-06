<?php

namespace App\Messaging\Contracts;

interface MessagePublisher
{
    /**
     * Опубликовать сообщение в очередь брокера.
     *
     * @param  array<string, mixed>  $payload
     */
    public function publish(string $queue, array $payload): void;
}
