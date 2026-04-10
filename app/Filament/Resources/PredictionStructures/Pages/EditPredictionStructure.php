<?php

namespace App\Filament\Resources\PredictionStructures\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\PredictionStructures\PredictionStructureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPredictionStructure extends EditRecord
{
    protected static string $resource = PredictionStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
