<?php

namespace App\Services;

use App\Enums\DeletionReason;
use App\Models\StoredFile;
use App\Notifications\Contracts\Notifier;
use App\Notifications\NotificationMessage;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

readonly class FileStorageService
{
    public function __construct(
        private FilesystemFactory $filesystem,
        private Notifier          $notifier,
        private LoggerInterface   $logger,
    ) {}

    /**
     * @throws Throwable
     */
    public function store(UploadedFile $file): StoredFile
    {
        $diskName = config('files.disk');
        $extension = strtolower($file->getClientOriginalExtension());

        $path = $file->storeAs(
            config('files.directory'),
            Str::uuid()->toString().'.'.$extension,
            $diskName,
        );

        if ($path === false) {
            throw new RuntimeException(__('app.errors.store_failed'));
        }

        try {
            return StoredFile::create([
                'original_name' => $file->getClientOriginalName(),
                'disk' => $diskName,
                'path' => $path,
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'extension' => $extension,
                'size' => $file->getSize(),
                'expires_at' => now()->addHours(config('files.ttl_hours')),
            ]);
        } catch (Throwable $e) {
            $this->filesystem->disk($diskName)->delete($path);

            throw $e;
        }
    }

    /**
     * Удаляет файл и отправляет уведомление.
     * Возвращает false, если файл уже удалён другим процессом
     * (например, планировщиком одновременно с ручным удалением) —
     * тогда повторное уведомление не отправляется.
     */
    public function delete(StoredFile $file, DeletionReason $reason): bool
    {
        // Удаление строки — атомарная «заявка» на файл
        $deleted = StoredFile::whereKey($file->getKey())->delete();

        if ($deleted === 0) {
            return false;
        }

        try {
            $this->filesystem->disk($file->disk)->delete($file->path);
        } catch (\Throwable $e) {
            $this->logger->warning('File record deleted but physical file removal failed', [
                'file_id' => $file->id,
                'path' => $file->path,
                'error' => $e->getMessage(),
            ]);
        }

        $this->notifier->notify(NotificationMessage::fileDeleted($file, $reason));

        return true;
    }
}
