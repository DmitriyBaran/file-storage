<?php

namespace App\Console\Commands;

use App\Messaging\Contracts\MessageConsumer;
use App\Messaging\Exceptions\UnprocessableMessageException;
use Illuminate\Console\Command;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;

/**
 * Воркер: читает очередь email-уведомлений из RabbitMQ и отправляет письма
 * через стандартный Laravel Mailer (в docker-compose — в Mailpit).
 */
class ConsumeEmailNotifications extends Command
{
    protected $signature = 'notifications:consume-email';

    protected $description = 'Слушать очередь RabbitMQ и отправлять email-уведомления';

    public function handle(MessageConsumer $consumer, Mailer $mailer): int
    {
        $queue = config('rabbitmq.queues.email');

        $this->info("Listening queue [{$queue}]...");

        $consumer->consume($queue, function (array $payload) use ($mailer) {
            if (empty($payload['to']) || empty($payload['subject'])) {
                throw new UnprocessableMessageException('Invalid email payload');
            }

            $mailer->raw($payload['body'] ?? '', function (Message $message) use ($payload) {
                $message->to($payload['to'])->subject($payload['subject']);
            });

            $this->line(sprintf('[%s] sent "%s" to %s', now()->toDateTimeString(), $payload['subject'], $payload['to']));
        });

        return self::SUCCESS;
    }
}
