<?php

namespace App\Filament\Resources\PredictionMethods\Pages;

use App\Filament\Resources\PredictionMethods\PredictionMethodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPredictionMethod extends EditRecord
{
    protected static string $resource = PredictionMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
