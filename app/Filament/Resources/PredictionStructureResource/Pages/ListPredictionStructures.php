<?php

namespace App\Filament\Resources\PredictionStructureResource\Pages;

use App\Filament\Resources\PredictionStructureResource;
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
