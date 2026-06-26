<?php

namespace Modules\PredictionWorkers\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class PredictionStat extends PredictionBaseModel
{
    protected $table = 'prediction_stats';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stats_date' => 'date',
            'payload' => 'array',
            'fetched_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeNewest(Builder $query): Builder
    {
        return $query
            ->orderByDesc('stats_date')
            ->orderByDesc('fetched_at')
            ->orderByDesc('id');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function storeDailySnapshot(string $serverUrl, CarbonInterface $date, array $payload): self
    {
        return static::updateOrCreate(
            [
                'server_url' => rtrim($serverUrl, '/'),
                'stats_date' => $date->toDateString(),
            ],
            [
                'payload' => $payload,
                'fetched_at' => now(),
            ],
        );
    }
}
