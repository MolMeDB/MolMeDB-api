<?php

namespace App\Console\Commands;

use App\Models\DownloadQueue;
use App\Models\Filesystem;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

#[Signature('downloader:cleanup-expired')]
#[Description('Delete files belonging to expired downloader exports while preserving their database history.')]
class CleanupExpiredDownloadFiles extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filesystem = Filesystem::where('type', Filesystem::TYPE_EXPORTS)->first();

        if (! $filesystem || ! $filesystem->isInitialized()) {
            $this->error('Export filesystem is not configured.');

            return self::FAILURE;
        }

        $disk = Storage::disk($filesystem->systemName);
        $deleted = 0;
        $failed = 0;

        DownloadQueue::query()
            ->expired()
            ->whereNull('files_deleted_at')
            ->orderBy('id')
            ->chunkById(200, function ($downloads) use ($disk, &$deleted, &$failed): void {
                foreach ($downloads as $download) {
                    try {
                        if (! $disk->deleteDirectory($download->storageDirectory())) {
                            throw new RuntimeException('Filesystem refused to delete the export directory.');
                        }

                        $download->forceFill([
                            'files_deleted_at' => now(),
                        ])->save();
                        $deleted++;
                    } catch (Throwable $exception) {
                        $failed++;
                        report($exception);
                        $this->error(sprintf(
                            'Failed to delete downloader files for %s: %s',
                            $download->uuid,
                            $exception->getMessage(),
                        ));
                    }
                }
            });

        $this->info(sprintf(
            'Expired downloader directories deleted: %d; failures: %d.',
            $deleted,
            $failed,
        ));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
