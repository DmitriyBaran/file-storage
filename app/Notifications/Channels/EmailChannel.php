<?php

namespace App\Notifications\Channels;

use App\Messaging\Contracts\MessagePublisher;
use App\Notifications\Contracts\NotificationChannel;
use App\Notifications\NotificationMessage;

/**
 * Email-канал: не шлёт письмо сам, а ставит задачу в очередь RabbitMQ.
 * Письмо отправляет отдельный воркер (php artisan notifications:consume-email).
 */
final readonly class EmailChannel implements NotificationChannel
{
    public function __construct(
        private MessagePublisher $publisher,
        private string           $queue,
        private string           $recipient,
    ) {}

    public function send(NotificationMessage $message): void
    {
        $this->publisher->publish($this->queue, [
            'to' => $this->recipient,
            'subject' => $message->subject,
            'body' => $message->body,
            'context' => $message->context,
        ]);
    }
}
