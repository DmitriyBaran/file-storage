<?php

namespace App\Messaging\Exceptions;

use RuntimeException;

/**
 * Сообщение обработать нельзя в принципе (битый payload и т.п.) — повторять бессмысленно.
 * Консьюмер сразу отправит его в очередь <queue>.failed, минуя повторы.
 */
class UnprocessableMessageException extends RuntimeException {}
