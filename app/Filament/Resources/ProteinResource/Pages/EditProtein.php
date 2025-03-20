<?php

namespace App\Filament\Resources\ProteinResource\Pages;

use App\Enums\IconEnums;
use App\Filament\Resources\ProteinResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProtein extends EditRecord
{
    protected static string $resource = ProteinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon(IconEnums::DELETE->value)
                ->modalHeading('Delete protein?')
                ->modalDescription('This action will delete all associated active interactions.')
                ->modalSubmitActionLabel('Understand. Delete'),
            Actions\ForceDeleteAction::make()
                ->icon(IconEnums::DELETE->value)
                ->modalHeading('Force delete protein?')
                ->modalDescription('This action will permanently delete all associated active interactions. This action is irreversible.')
                ->modalSubmitActionLabel('Understand. Delete'),
            Actions\RestoreAction::make()
                ->icon(IconEnums::RESTORE->value)
                ->modalHeading('Restore protein?')
                ->modalDescription('Do you want to restore the protein record? This step will 
                    restore all related active interactions.')
        ];
    }

    public function getTitle(): string
    {
        return ($this->record->trashed() ? '(DELETED) ' : "") .  
            "Edit protein [ID:" . $this->record->id . "] - " . $this->record->uniprot_id;
    }
}
