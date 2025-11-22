<?php

namespace App\Filament\Resources\PredictionDatasetResource\Pages;

use App\Filament\Resources\PredictionDatasetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPredictionDatasets extends ListRecords
{
    protected static string $resource = PredictionDatasetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
