<?php

namespace App\Filament\Resources\PublicationResource\Pages;

use App\Enums\IconEnums;
use App\Filament\Resources\PublicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPublication extends EditRecord
{
    protected static string $resource = PublicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon(IconEnums::DELETE->value)
                ->modalHeading('Delete publication?')
                ->modalDescription('This action will delete all associated datasets and interactions.')
                ->modalSubmitActionLabel('Understand. Delete'),
            Actions\ForceDeleteAction::make()
                ->icon(IconEnums::DELETE->value)
                ->modalHeading('Force delete publication?')
                ->modalDescription('This action will permanently delete all associated datasets and interactions. This action is irreversible.')
                ->modalSubmitActionLabel('Understand. Delete'),
            Actions\RestoreAction::make()
                ->icon(IconEnums::RESTORE->value)
                ->modalHeading('Restore publication?')
                ->modalDescription('Do you want to restore the publication record? This step will 
                    restore all related interactions and datasets.')
        ];
    }

    public function getTitle(): string
    {
        return ($this->record->trashed() ? '(DELETED) ' : "") . 
            "Edit publication [ID:" . $this->record->id . "]";
    }
}
