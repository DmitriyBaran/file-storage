<?php

namespace App\Notifications\Channels;

use App\Notifications\Contracts\NotificationChannel;
use App\Notifications\NotificationMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\RequestException;

/**
 * Пример расширения: канал Telegram. Выключен по умолчанию,
 * включается через NOTIFICATION_CHANNELS=email,telegram.
 * Ни сервис файлов, ни Notifier ради него не менялись.
 */
final readonly class TelegramChannel implements NotificationChannel
{
    public function __construct(
        private HttpClient $http,
        private ?string    $botToken,
        private ?string    $chatId,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function send(NotificationMessage $message): void
    {
        if (! $this->botToken || ! $this->chatId) {
            throw new \RuntimeException('Telegram channel is enabled but TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID are not set.');
        }

        $this->http
            ->timeout(5)
            ->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => "{$message->subject}\n\n{$message->body}",
            ])
            ->throw();
    }
}
