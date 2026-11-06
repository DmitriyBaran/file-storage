<?php

namespace App\Notifications;

use App\Notifications\Contracts\NotificationChannel;
use App\Notifications\Contracts\Notifier;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Рассылает уведомление во все включённые каналы.
 * Сбой одного канала не мешает остальным и не ломает основную операцию.
 */
final readonly class MultiChannelNotifier implements Notifier
{
    /**
     * @param  iterable<NotificationChannel>  $channels
     */
    public function __construct(
        private iterable        $channels,
        private LoggerInterface $logger,
    ) {}

    public function notify(NotificationMessage $message): void
    {
        foreach ($this->channels as $channel) {
            try {
                $channel->send($message);
            } catch (Throwable $e) {
                $this->logger->error('Notification channel failed', [
                    'channel' => $channel::class,
                    'subject' => $message->subject,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
