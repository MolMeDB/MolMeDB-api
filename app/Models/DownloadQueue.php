<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DownloadQueue extends Model
{
    protected $table = 'download_queue';

    protected $guarded = [];

    public const EXPIRATION_DAYS = 2;

    public const STALLED_AFTER_MINUTES = 5;

    public const FILTER_VERSION = 2;

    const STATE_PENDING = 0;

    const STATE_RUNNING = 1;

    const STATE_DONE = 2;

    const STATE_ERROR = 3;

    public static $states = [
        self::STATE_PENDING => 'pending',
        self::STATE_RUNNING => 'running',
        self::STATE_DONE => 'done',
        self::STATE_ERROR => 'error',
    ];

    protected function casts(): array
    {
        return [
            'selection' => 'array',
            'progress' => 'array',
            'expires_at' => 'datetime',
            'files_deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function transitionToState(int $state, ?string $errorMessage = null): void
    {
        $this->state = $state;
        $this->error_message = $errorMessage;
        $this->save();
    }

    public function beginProcessing(string $runToken): bool
    {
        $currentToken = data_get($this->progress, 'run_token');

        if ($currentToken && $currentToken !== $runToken) {
            return false;
        }

        $this->state = self::STATE_RUNNING;
        $this->error_message = null;
        $this->progress = array_merge($this->progress ?? [], [
            'run_token' => $runToken,
            'updated_at' => now()->toISOString(),
        ]);
        $this->save();

        return true;
    }

    public function updateProgress(int $processed, int $total, string $runToken): bool
    {
        $progress = [
            'processed' => $processed,
            'total' => $total,
            'percent' => $total > 0 ? min(100, round(($processed / $total) * 100, 1)) : 100,
            'run_token' => $runToken,
            'updated_at' => now()->toISOString(),
        ];

        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('progress->run_token', $runToken)
            ->update([
                'progress' => $progress,
                'updated_at' => now(),
            ]);

        if ($updated === 1) {
            $this->progress = $progress;
        }

        return $updated === 1;
    }

    public function completeProcessing(string $runToken, string $filePath): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->where('progress->run_token', $runToken)
            ->update([
                'state' => self::STATE_DONE,
                'file_path' => $filePath,
                'error_message' => null,
                'updated_at' => now(),
            ]) === 1;
    }

    public function failProcessing(string $runToken, string $errorMessage): void
    {
        static::query()
            ->whereKey($this->getKey())
            ->where('progress->run_token', $runToken)
            ->update([
                'state' => self::STATE_ERROR,
                'error_message' => $errorMessage,
                'updated_at' => now(),
            ]);
    }

    public function prepareForRestart(string $runToken): void
    {
        $total = (int) data_get($this->progress, 'total', 0);

        $this->state = self::STATE_PENDING;
        $this->file_path = null;
        $this->error_message = null;
        $this->progress = [
            'processed' => 0,
            'total' => $total,
            'percent' => 0,
            'run_token' => $runToken,
            'restarted_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];
        $this->save();
    }

    public function isStalled(): bool
    {
        if ($this->state !== self::STATE_RUNNING) {
            return false;
        }

        $lastProgressAt = data_get($this->progress, 'updated_at');

        if (! is_string($lastProgressAt) || $lastProgressAt === '') {
            return false;
        }

        return Carbon::parse($lastProgressAt)
            ->lte(now()->subMinutes(self::STALLED_AFTER_MINUTES));
    }

    /**
     * @param  array<string, mixed>  $selection
     * @return array<string, mixed>
     */
    public static function normalizeSelection(array $selection): array
    {
        $membraneIds = array_values(array_unique(array_map('intval', $selection['membrane_ids'] ?? [])));
        $methodIds = array_values(array_unique(array_map('intval', $selection['method_ids'] ?? [])));
        $proteinIds = array_values(array_unique(array_map('intval', $selection['protein_ids'] ?? [])));
        $structureIdentifiers = array_values(array_unique(array_filter(array_map(
            static fn (mixed $identifier): string => trim((string) $identifier),
            $selection['structure_identifiers'] ?? [],
        ))));

        sort($membraneIds, SORT_NUMERIC);
        sort($methodIds, SORT_NUMERIC);
        sort($proteinIds, SORT_NUMERIC);
        sort($structureIdentifiers, SORT_STRING);

        return [
            'filter_version' => self::FILTER_VERSION,
            'membrane_ids' => $membraneIds,
            'method_ids' => $methodIds,
            'protein_ids' => $proteinIds,
            'structure_identifiers' => $structureIdentifiers,
        ];
    }

    /**
     * @param  array<string, mixed>  $selection
     */
    public static function hashSelection(array $selection): string
    {
        return hash('sha256', json_encode(
            static::normalizeSelection($selection),
            JSON_THROW_ON_ERROR,
        ));
    }

    public function expirationDate(): CarbonInterface
    {
        return $this->expires_at
            ?? $this->created_at->copy()->addDays(self::EXPIRATION_DAYS);
    }

    public function isExpired(): bool
    {
        return $this->expirationDate()->isPast();
    }

    public function storageDirectory(): string
    {
        return 'downloader/'.$this->uuid;
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('expires_at', '<=', now())
                ->orWhere(function (Builder $query): void {
                    $query
                        ->whereNull('expires_at')
                        ->where('created_at', '<=', now()->subDays(self::EXPIRATION_DAYS));
                });
        });
    }
}
