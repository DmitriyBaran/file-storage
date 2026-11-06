<?php

namespace App\Providers;

use App\Messaging\Contracts\MessageConsumer;
use App\Messaging\Contracts\MessagePublisher;
use App\Messaging\RabbitMq\RabbitMqConnection;
use App\Messaging\RabbitMq\RabbitMqConsumer;
use App\Messaging\RabbitMq\RabbitMqPublisher;
use Illuminate\Support\ServiceProvider;

class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            RabbitMqConnection::class,
            fn ($app) => new RabbitMqConnection($app['config']->get('rabbitmq')),
        );

        $this->app->when(RabbitMqConsumer::class)
            ->needs('$maxAttempts')
            ->giveConfig('rabbitmq.retry.max_attempts');
        $this->app->when(RabbitMqConsumer::class)
            ->needs('$retryDelayMs')
            ->giveConfig('rabbitmq.retry.delay_ms');

        // Бизнес-код зависит от интерфейсов; смена брокера = смена биндинга.
        $this->app->singleton(MessagePublisher::class, RabbitMqPublisher::class);
        $this->app->singleton(MessageConsumer::class, RabbitMqConsumer::class);
    }
}
