<?php

namespace App\Filament\Resources\PredictionStructureResource\Pages;

use App\Filament\Resources\PredictionStructureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPredictionStructure extends EditRecord
{
    protected static string $resource = PredictionStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
