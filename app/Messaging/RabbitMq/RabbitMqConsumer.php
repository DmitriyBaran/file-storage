<?php

namespace App\Messaging\RabbitMq;

use App\Messaging\Contracts\MessageConsumer;
use App\Messaging\Exceptions\UnprocessableMessageException;
use Exception;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Читает очередь и при ошибке обработки повторяет сообщение с задержкой.
 *
 * Схема очередей для очереди <queue>:
 *  - <queue>.retry  — сюда кладётся упавшее сообщение с TTL; по истечении TTL
 *                     RabbitMQ сам возвращает его в <queue> (dead-letter в default exchange);
 *  - <queue>.failed — сообщения, для которых кончились попытки или которые не разобрать.
 * Число неудачных попыток хранится в заголовке сообщения.
 */
readonly class RabbitMqConsumer implements MessageConsumer
{
    public const FAILURES_HEADER = 'x-failures';

    public function __construct(
        private RabbitMqConnection $connection,
        private LoggerInterface    $logger,
        private int                $maxAttempts,
        private int                $retryDelayMs,
    ) {}

    /**
     * @throws Exception
     */
    public function consume(string $queue, callable $handler): void
    {
        $channel = $this->connection->declareQueue($queue);

        // TTL задаётся на каждом сообщении, а не на очереди: тогда задержку можно менять
        // в .env без пересоздания очереди (у очереди с другими аргументами declare упадёт)
        $this->connection->declareQueue(self::retryQueue($queue), [
            'x-dead-letter-exchange' => '',
            'x-dead-letter-routing-key' => $queue,
        ]);
        $this->connection->declareQueue(self::failedQueue($queue));

        // Берём по одному сообщению, пока не подтвердим предыдущее
        $channel->basic_qos(0, 1, false);

        $channel->basic_consume(
            $queue,
            '',
            false,
            false, // no_ack = false — подтверждаем вручную
            false,
            false,
            fn (AMQPMessage $message) => $this->process($message, $queue, $handler),
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    /**
     * Обработка одного сообщения. Вынесена отдельно, чтобы логику повторов
     * можно было проверить без живого брокера.
     *
     * @param  callable(array<string, mixed>): void  $handler
     */
    public function process(AMQPMessage $message, string $queue, callable $handler): void
    {
        $failures = self::failures($message) + 1;

        try {
            $handler(json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR));
        } catch (JsonException|UnprocessableMessageException $e) {
            // Повтор ничего не изменит
            $this->moveTo(self::failedQueue($queue), $message, $failures, $e, $queue);

            return;
        } catch (Throwable $e) {
            $target = $failures < $this->maxAttempts ? self::retryQueue($queue) : self::failedQueue($queue);
            $this->moveTo($target, $message, $failures, $e, $queue);

            return;
        }

        $message->ack();
    }

    public static function retryQueue(string $queue): string
    {
        return $queue.'.retry';
    }

    public static function failedQueue(string $queue): string
    {
        return $queue.'.failed';
    }

    public static function failures(AMQPMessage $message): int
    {
        if (! $message->has('application_headers')) {
            return 0;
        }

        return (int) ($message->get('application_headers')->getNativeData()[self::FAILURES_HEADER] ?? 0);
    }

    private function moveTo(string $target, AMQPMessage $message, int $failures, Throwable $e, string $queue): void
    {
        $isRetry = $target === self::retryQueue($queue);

        $properties = [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'application_headers' => new AMQPTable([
                self::FAILURES_HEADER => $failures,
                'x-last-error' => mb_substr($e->getMessage(), 0, 255),
            ]),
        ];

        if ($isRetry) {
            $properties['expiration'] = (string) $this->retryDelayMs;
        }

        // Сначала публикуем копию, потом подтверждаем оригинал: если упадём между шагами,
        // сообщение придёт ещё раз (at-least-once), но не потеряется
        $message->getChannel()->basic_publish(new AMQPMessage($message->getBody(), $properties), '', $target);
        $message->ack();

        $context = [
            'queue' => $queue,
            'attempt' => $failures,
            'max_attempts' => $this->maxAttempts,
            'error' => $e->getMessage(),
        ];

        if ($isRetry) {
            $this->logger->warning("Message processing failed, retry in {$this->retryDelayMs} ms", $context);
        } else {
            $this->logger->error("Message moved to [{$target}]", $context + ['body' => $message->getBody()]);
        }
    }
}
