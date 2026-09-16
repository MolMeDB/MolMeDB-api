<?php

namespace App\Filament\Resources\PredictionStatsResource\Pages;

use App\Filament\Resources\PredictionStatsResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPredictionStats extends ViewRecord
{
    protected static string $resource = PredictionStatsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
