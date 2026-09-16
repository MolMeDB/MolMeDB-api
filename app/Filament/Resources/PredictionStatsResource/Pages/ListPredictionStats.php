<?php

namespace App\Filament\Resources\PredictionStatsResource\Pages;

use App\Filament\Resources\PredictionStatsResource;
use Filament\Resources\Pages\ListRecords;

class ListPredictionStats extends ListRecords
{
    protected static string $resource = PredictionStatsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
