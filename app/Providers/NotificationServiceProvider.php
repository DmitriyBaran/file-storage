<?php

namespace App\Providers;

use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Contracts\NotificationChannel;
use App\Notifications\Contracts\Notifier;
use App\Notifications\MultiChannelNotifier;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Скалярные зависимости каналов берём из конфига
        $this->app->when(EmailChannel::class)
            ->needs('$queue')
            ->giveConfig('rabbitmq.queues.email');
        $this->app->when(EmailChannel::class)
            ->needs('$recipient')
            ->giveConfig('notifications.email.to');

        $this->app->when(TelegramChannel::class)
            ->needs('$botToken')
            ->giveConfig('notifications.telegram.bot_token');
        $this->app->when(TelegramChannel::class)
            ->needs('$chatId')
            ->giveConfig('notifications.telegram.chat_id');

        $this->app->singleton(Notifier::class, function (Application $app): Notifier {
            return new MultiChannelNotifier(
                $this->resolveChannels($app),
                $app->make(LoggerInterface::class),
            );
        });
    }

    /**
     * @return list<NotificationChannel>
     */
    private function resolveChannels(Application $app): array
    {
        $config = $app['config'];
        $drivers = $config->get('notifications.drivers', []);

        return array_map(function (string $name) use ($app, $drivers): NotificationChannel {
            $class = $drivers[$name] ?? throw new InvalidArgumentException(
                "Unknown notification channel [{$name}]. Register it in config/notifications.php."
            );

            $channel = $app->make($class);

            if (! $channel instanceof NotificationChannel) {
                throw new InvalidArgumentException("[{$class}] must implement ".NotificationChannel::class);
            }

            return $channel;
        }, $config->get('notifications.channels', []));
    }
}
