<?php

namespace Tests\Unit;

use App\Messaging\Exceptions\UnprocessableMessageException;
use App\Messaging\RabbitMq\RabbitMqConnection;
use App\Messaging\RabbitMq\RabbitMqConsumer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class RabbitMqConsumerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const QUEUE = 'notifications.email';

    private AMQPChannel&MockInterface $channel;

    /** @var list<array{queue: string, message: AMQPMessage}> */
    private array $published = [];

    private RabbitMqConsumer $consumer;

    protected function setUp(): void
    {
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->channel->allows('basic_publish')->andReturnUsing(function (AMQPMessage $message, string $exchange, string $queue) {
            $this->published[] = ['queue' => $queue, 'message' => $message];
        });

        $this->consumer = new RabbitMqConsumer(
            Mockery::mock(RabbitMqConnection::class),
            new NullLogger,
            maxAttempts: 3,
            retryDelayMs: 1000,
        );
    }

    private function delivery(string $body = '{"to":"a@b.c"}', int $failures = 0): AMQPMessage
    {
        $properties = $failures > 0
            ? ['application_headers' => new AMQPTable([RabbitMqConsumer::FAILURES_HEADER => $failures])]
            : [];

        $message = new AMQPMessage($body, $properties);
        $message->setChannel($this->channel);
        $message->setDeliveryInfo(1, false, '', self::QUEUE);

        return $message;
    }

    private function failing(): callable
    {
        return fn () => throw new RuntimeException('SMTP is down');
    }

    public function test_successful_message_is_acked_without_republishing(): void
    {
        $this->channel->expects('basic_ack')->once();
        $received = null;

        $this->consumer->process($this->delivery(), self::QUEUE, function (array $payload) use (&$received) {
            $received = $payload;
        });

        $this->assertSame(['to' => 'a@b.c'], $received);
        $this->assertSame([], $this->published);
    }

    public function test_failed_message_goes_to_retry_queue_with_delay_and_counter(): void
    {
        $this->channel->expects('basic_ack')->once();

        $this->consumer->process($this->delivery(), self::QUEUE, $this->failing());

        $this->assertCount(1, $this->published);
        $this->assertSame('notifications.email.retry', $this->published[0]['queue']);
        $this->assertSame('1000', $this->published[0]['message']->get('expiration'));
        $this->assertSame(1, RabbitMqConsumer::failures($this->published[0]['message']));
        $this->assertSame('{"to":"a@b.c"}', $this->published[0]['message']->getBody());
    }

    public function test_message_goes_to_failed_queue_after_last_attempt(): void
    {
        $this->channel->expects('basic_ack')->once();

        // Две неудачи уже были, это третья (последняя) попытка
        $this->consumer->process($this->delivery(failures: 2), self::QUEUE, $this->failing());

        $this->assertSame('notifications.email.failed', $this->published[0]['queue']);
        $this->assertSame(3, RabbitMqConsumer::failures($this->published[0]['message']));
        $this->assertFalse($this->published[0]['message']->has('expiration'));
    }

    public function test_broken_json_goes_to_failed_queue_without_retries(): void
    {
        $this->channel->expects('basic_ack')->once();

        $this->consumer->process($this->delivery('not json'), self::QUEUE, fn () => null);

        $this->assertSame('notifications.email.failed', $this->published[0]['queue']);
    }

    public function test_unprocessable_message_goes_to_failed_queue_without_retries(): void
    {
        $this->channel->expects('basic_ack')->once();

        $this->consumer->process($this->delivery(), self::QUEUE, fn () => throw new UnprocessableMessageException('Invalid'));

        $this->assertSame('notifications.email.failed', $this->published[0]['queue']);
    }
}
