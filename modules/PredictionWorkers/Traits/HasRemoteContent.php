<?php

namespace Modules\PredictionWorkers\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use Throwable;

trait HasRemoteContent
{
    /** @var FilesystemAdapter|null */
    protected ?FilesystemAdapter $remoteFs = null;

    /**
     * Disk name
     * - Can be changed in child classes
     */
    protected string $remoteDisk = 'cosmo_runner';

    /**
     * Lazy getting cache FilesystemAdapter
     */
    public function remote(?FilesystemAdapter $fs = null): FilesystemAdapter
    {
        if($fs)
        {
            $this->remoteFs = $fs;
        }
        else if (!$this->remoteFs) {
            $this->remoteFs = Storage::disk($this->remoteDisk());
        }
        return $this->remoteFs;
    }

    protected function remoteDisk(): string
    {
        return $this->remoteDisk;
    }

    protected function remoteFiles(string $path = '', bool $recursive = false): array
    {
        return $recursive
            ? $this->remote()->allFiles($path)
            : $this->remote()->files($path);
    }

    protected function remoteDirs(string $path = '', bool $recursive = false): array
    {
        return $recursive
            ? $this->remote()->allDirectories($path)
            : $this->remote()->directories($path);
    }

    protected function remoteExists(string $path): bool
    {
        return $this->remote()->exists($path);
    }

    protected function remoteReadStream(string $path)
    {
        return $this->remote()->readStream($path);
    }

    protected function downloadTo(string $remotePath, string $localPath): bool
    {
        $in = $this->remoteReadStream($remotePath);
        if ($in === false || $in === null) {
            return false;
        }

        @mkdir(\dirname($localPath), 0775, true);
        $out = \fopen($localPath, 'w');
        \stream_copy_to_stream($in, $out);
        \fclose($out);
        if (\is_resource($in)) \fclose($in);

        return true;
    }

    protected function withRetry(callable $fn, int $tries = 3, int $baseSleepMs = 200)
    {
        $attempt = 0;
        retry:
        try {
            return $fn();
        } catch (Throwable $e) {
            $attempt++;
            if ($attempt >= $tries) {
                throw $e;
            }
            \usleep($baseSleepMs * (2 ** ($attempt - 1)) * 1000);
            goto retry;
        }
    }
}