<?php

namespace Tests;

use App\Messaging\Contracts\MessagePublisher;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\FakePublisher;

abstract class TestCase extends BaseTestCase
{
    protected FakePublisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        // Подменяем брокер: тестам не нужен живой RabbitMQ
        $this->publisher = new FakePublisher;
        $this->app->instance(MessagePublisher::class, $this->publisher);
    }
}
