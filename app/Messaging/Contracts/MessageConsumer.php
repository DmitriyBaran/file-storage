<?php

namespace App\Messaging\Contracts;

interface MessageConsumer
{
    /**
     * Слушать очередь и передавать каждое сообщение в обработчик.
     * Если обработчик бросает исключение, сообщение повторяется с задержкой,
     * а после исчерпания попыток откладывается в отдельную очередь.
     * UnprocessableMessageException — откладывается сразу, без повторов.
     *
     * @param  callable(array<string, mixed>): void  $handler
     */
    public function consume(string $queue, callable $handler): void;
}
