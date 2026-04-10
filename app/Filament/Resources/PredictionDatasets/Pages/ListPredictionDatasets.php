<?php

namespace App\Filament\Resources\PredictionDatasets\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PredictionDatasets\PredictionDatasetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPredictionDatasets extends ListRecords
{
    protected static string $resource = PredictionDatasetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
