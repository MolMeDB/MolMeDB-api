<?php

namespace App\Filament\Resources\PredictionDatasets\Pages;

use Filament\Actions\DeleteAction;
use App\Enums\IconEnums;
use App\Filament\Resources\PredictionDatasets\PredictionDatasetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPredictionDataset extends EditRecord
{
    protected static string $resource = PredictionDatasetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return "Edit prediction dataset";
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }
}
