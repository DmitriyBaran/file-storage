<?php

return [

    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
    'port' => (int) env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    'queues' => [
        'email' => env('RABBITMQ_EMAIL_QUEUE', 'notifications.email'),
    ],

    /*
     | Повторы при ошибке обработки (например, SMTP недоступен).
     | Всего попыток max_attempts, между ними delay_ms. После последней неудачи
     | сообщение уходит в очередь <queue>.failed — его видно в RabbitMQ UI.
     */
    'retry' => [
        'max_attempts' => (int) env('RABBITMQ_RETRY_ATTEMPTS', 5),
        'delay_ms' => (int) env('RABBITMQ_RETRY_DELAY_MS', 30000),
    ],

];
