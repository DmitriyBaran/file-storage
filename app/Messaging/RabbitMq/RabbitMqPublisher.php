<?php

namespace App\Messaging\RabbitMq;

use App\Messaging\Contracts\MessagePublisher;
use Exception;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;

readonly class RabbitMqPublisher implements MessagePublisher
{
    public function __construct(private RabbitMqConnection $connection) {}

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function publish(string $queue, array $payload): void
    {
        $channel = $this->connection->declareQueue($queue);

        $message = new AMQPMessage(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ],
        );

        // Публикация в default exchange: routing key = имя очереди
        $channel->basic_publish($message, '', $queue);
    }
}
