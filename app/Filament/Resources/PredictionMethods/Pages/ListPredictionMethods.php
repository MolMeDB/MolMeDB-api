<?php

namespace App\Filament\Resources\PredictionMethods\Pages;

use App\Filament\Resources\PredictionMethods\PredictionMethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPredictionMethods extends ListRecords
{
    protected static string $resource = PredictionMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
