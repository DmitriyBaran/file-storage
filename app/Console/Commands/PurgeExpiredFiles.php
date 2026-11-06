<?php

namespace App\Console\Commands;

use App\Enums\DeletionReason;
use App\Models\StoredFile;
use App\Services\FileStorageService;
use Illuminate\Console\Command;

class PurgeExpiredFiles extends Command
{
    protected $signature = 'files:purge-expired';

    protected $description = 'Удалить файлы с истёкшим сроком хранения и отправить уведомления';

    public function handle(FileStorageService $service): int
    {
        $count = 0;

        StoredFile::expired()->chunkById(100, function ($files) use ($service, &$count) {
            foreach ($files as $file) {
                if ($service->delete($file, DeletionReason::Expired)) {
                    $count++;
                }
            }
        });

        $this->info("Удалено файлов: {$count}");

        return self::SUCCESS;
    }
}
