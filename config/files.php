<?php

return [

    // Диск Laravel Filesystem, на котором хранятся загруженные файлы.
    'disk' => env('FILES_DISK', 'local'),

    // Директория внутри диска.
    'directory' => env('FILES_DIRECTORY', 'uploads'),

    // Максимальный размер файла в килобайтах (10 MB).
    'max_size_kb' => (int) env('FILES_MAX_SIZE_KB', 10240),

    // Через сколько часов файл удаляется автоматически.
    'ttl_hours' => (int) env('FILES_TTL_HOURS', 24),

    // Максимальная длина исходного имени файла — по размеру колонки original_name.
    'max_name_length' => 255,

    // Разрешённые расширения (проверяются и по содержимому, и по имени).
    'allowed_extensions' => ['pdf', 'docx'],

];
