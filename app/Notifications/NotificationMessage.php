<?php

namespace App\Notifications;

use App\Enums\DeletionReason;
use App\Models\StoredFile;

/**
 * Канало-независимое уведомление: каждый канал сам решает, как его доставить.
 */
final readonly class NotificationMessage
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $subject,
        public string $body,
        public array $context = [],
    ) {}

    public static function fileDeleted(StoredFile $file, DeletionReason $reason): self
    {
        // Язык из конфига, а не текущий: уведомление одно для всех, кто бы ни удалил файл
        $locale = config('notifications.locale');

        $body = __('app.notification.body', [
            'name' => $file->original_name,
            'size' => $file->humanSize(),
            'reason' => $reason->label($locale),
            'uploaded' => $file->created_at?->toDateTimeString() ?? '—',
            'deleted' => now()->toDateTimeString(),
        ], $locale);

        return new self(
            subject: __('app.notification.subject', ['name' => $file->original_name], $locale),
            body: $body,
            context: [
                'event' => 'file.deleted',
                'reason' => $reason->value,
                'file' => [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'size' => $file->size,
                    'uploaded_at' => $file->created_at?->toIso8601String(),
                ],
                'deleted_at' => now()->toIso8601String(),
            ],
        );
    }
}
