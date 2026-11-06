<?php

use Illuminate\Support\Facades\Schedule;

// Удаление файлов, у которых истёк срок хранения (24 часа по умолчанию).
Schedule::command('files:purge-expired')
    ->everyMinute()
    ->withoutOverlapping();
