<?php

namespace App\Filament\Resources\Predictions\Pages;

use App\Enums\IconEnums;
use App\Filament\Resources\Predictions\PredictionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrediction extends EditRecord
{
    protected static string $resource = PredictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return "Prediction request detail";
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Details';
    }
    
    public function getContentTabIcon(): ?string
    {
        return IconEnums::VIEW->value;
    }

    protected function getFormActions(): array
    {
        return []; // Nothing to edit
    }
}
