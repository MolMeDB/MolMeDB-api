<?php

namespace App\Filament\Resources\Membranes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Enums\IconEnums;
use App\Filament\Resources\Membranes\MembraneResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMembrane extends EditRecord
{
    protected static string $resource = MembraneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()  
                ->modalHeading('Delete membrane?')
                ->modalDescription('Deleting membrane will delete all associated files, datasets and interactions.')
                ->modalSubmitActionLabel('Understand. Delete'),
            ForceDeleteAction::make()  
                ->modalHeading('Delete membrane?')
                ->modalDescription('Force deleting membrane will delete all associated files, datasets and interactions. This step is irreversible.')
                ->modalSubmitActionLabel('Understand. Delete all.'),
            RestoreAction::make()
                ->icon(IconEnums::RESTORE->value)
                ->modalHeading('Restore membrane?')
                ->modalDescription('Warning! All associated files, datasets and interactions will be also restored and be directly visible.')
                ->modalSubmitActionLabel('Understand. Restore')
        ];
    }

    public function getTitle(): string
    {
        return ($this->record->trashed() ? '(DELETED) ' : "") .  
            "Edit membrane [ID:" . $this->record->id . "] - " . $this->record->abbreviation;
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
        return IconEnums::MEMBRANE->value;
    }

    // public function getSubheading(): string|Htmlable|null
    // {
    //     return "Remember, that modifying this record leads to inconsistent data and should be done with caution!";
    // }
}
