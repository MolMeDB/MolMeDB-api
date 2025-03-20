<?php

namespace App\Filament\Resources\DatasetResource\Pages;

use App\Filament\Resources\DatasetResource;
use App\Models\Dataset;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EditDataset extends EditRecord
{
    protected static string $resource = DatasetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->modalDescription('Deleting dataset will delete all interactions and structures identifiers associated to it.')
                ->modalSubmitActionLabel('Delete dataset'),
            Actions\ForceDeleteAction::make()
                ->modalDescription('Danger! Deleting dataset will delete all records associated to it! This step is irreversible.')
                ->modalSubmitActionLabel('Delete forever.'),
            Actions\RestoreAction::make()
                ->modalDescription('Warning! All assigned interactions and identifiers will be also restored.')
                ->modalSubmitActionLabel('Understand. Restore all.')
                ->disabled(fn (Dataset $record) => !$record->isRestoreable()),
        ];
    }

    public function getTitle() : string {
        return ($this->record->trashed() ? '(DELETED) ' : "") . 
            "Edit dataset [ID: {$this->record->id}] - {$this->record->name}";
    }
}
