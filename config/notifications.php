<?php

use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\TelegramChannel;

return [

    /*
     | Включённые каналы, через запятую: NOTIFICATION_CHANNELS=email,telegram
     */
    'channels' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('NOTIFICATION_CHANNELS', 'email'))
    ))),

    /*
     | Реестр драйверов: имя канала => класс, реализующий NotificationChannel.
     | Чтобы добавить новый способ доставки, достаточно написать класс
     | и зарегистрировать его здесь — основной код не меняется.
     */
    'drivers' => [
        'email' => EmailChannel::class,
        'telegram' => TelegramChannel::class,
    ],

    /*
     | Язык уведомлений. Не зависит от языка, выбранного в интерфейсе:
     | файл может удалить и посетитель, и планировщик, а письмо получает один и тот же админ.
     */
    'locale' => env('NOTIFICATION_LOCALE', env('APP_LOCALE', 'en')),

    'email' => [
        'to' => env('NOTIFICATION_EMAIL', 'admin@example.com'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

];
