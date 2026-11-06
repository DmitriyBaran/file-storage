<?php

namespace App\Messaging\RabbitMq;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

/**
 * Ленивое соединение с RabbitMQ: подключаемся только при первом обращении.
 */
class RabbitMqConnection
{
    private ?AMQPStreamConnection $connection = null;

    private ?AMQPChannel $channel = null;

    /** @var array<string, true> */
    private array $declaredQueues = [];

    public function __construct(private readonly array $config) {}

    /**
     * @throws Exception
     */
    public function channel(): AMQPChannel
    {
        if ($this->channel !== null && $this->channel->is_open()) {
            return $this->channel;
        }

        if ($this->connection === null || ! $this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                $this->config['host'],
                $this->config['port'],
                $this->config['user'],
                $this->config['password'],
                $this->config['vhost'],
            );
            $this->declaredQueues = [];
        }

        return $this->channel = $this->connection->channel();
    }

    /**
     * @param  array<string, mixed>  $arguments  аргументы очереди (x-dead-letter-exchange и т.п.)
     *
     * @throws Exception
     */
    public function declareQueue(string $queue, array $arguments = []): AMQPChannel
    {
        $channel = $this->channel();

        if (! isset($this->declaredQueues[$queue])) {
            // durable = true: очередь переживает рестарт брокера
            $channel->queue_declare($queue, false, true, false, false, false, new AMQPTable($arguments));
            $this->declaredQueues[$queue] = true;
        }

        return $channel;
    }

    public function close(): void
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (Throwable) {
            // соединение уже разорвано — нечего закрывать
        } finally {
            $this->channel = null;
            $this->connection = null;
            $this->declaredQueues = [];
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
