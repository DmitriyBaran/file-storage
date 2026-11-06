<?php

namespace App\Notifications\Contracts;

use App\Notifications\NotificationMessage;

/**
 * То, от чего зависит бизнес-код. Он не знает, какие каналы включены
 * и как они устроены.
 */
interface Notifier
{
    public function notify(NotificationMessage $message): void;
}
