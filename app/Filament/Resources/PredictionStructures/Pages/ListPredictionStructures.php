<?php

namespace App\Filament\Resources\PredictionStructures\Pages;

use App\Filament\Resources\PredictionStructures\PredictionStructureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPredictionStructures extends ListRecords
{
    protected static string $resource = PredictionStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
