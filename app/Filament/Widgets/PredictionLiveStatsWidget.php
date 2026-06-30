<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PredictionStatsResource;
use Carbon\CarbonInterface;
use Filament\Widgets\Widget;
use Modules\PredictionWorkers\Models\PredictionStat;

class PredictionLiveStatsWidget extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.prediction-live-stats';

    /**
     * @var array<string, int>
     */
    public array $queue = [];

    public ?CarbonInterface $lastUpdatedAt = null;

    public ?string $detailUrl = null;

    public function mount(): void
    {
        $record = PredictionStat::query()->newest()->first();

        // No daily snapshot fetched yet (e.g. fresh install) - the chart view
        // already renders everything as 0 when $queue is empty.
        $this->queue = $record?->payload['queue'] ?? [];
        $this->lastUpdatedAt = $record?->fetched_at;
        $this->detailUrl = $record ? PredictionStatsResource::getUrl('view', ['record' => $record]) : null;
    }
}
