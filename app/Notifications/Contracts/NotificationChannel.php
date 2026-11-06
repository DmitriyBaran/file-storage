<?php

namespace App\Notifications\Contracts;

use App\Notifications\NotificationMessage;

/**
 * Способ доставки уведомления (email, telegram, slack, ...).
 * Новый канал = новый класс, реализующий этот интерфейс,
 * + строка в config/notifications.php.
 */
interface NotificationChannel
{
    public function send(NotificationMessage $message): void;
}
