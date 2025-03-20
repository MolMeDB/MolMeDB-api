<?php

namespace App\Filament\Resources\StructureResource\Pages;

use App\Enums\IconEnums;
use App\Filament\Resources\StructureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStructure extends EditRecord
{
    protected static string $resource = StructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()  
                ->modalHeading('Delete structure?')
                ->modalDescription('This action will delete all associated ions and their interactions.')
                ->modalSubmitActionLabel('Understand. Delete'),
            Actions\RestoreAction::make()
                ->icon(IconEnums::RESTORE->value)
                ->modalHeading('Restore structure?')
                ->modalDescription('Warning! All associated files, datasets and interactions will also be restored and probably will be directly visible.')
                ->modalSubmitActionLabel('Understand. Restore')
        ];
    }

    public function getBreadcrumbs(): array
    { 
        $parent = $this->record->parent()->withTrashed()->first();
        if(!$parent)
            return [
                static::getResource()::getUrl('index') => 'List of structures',
                static::getResource()::getUrl('edit', ['record' => $this->record]) => 'Structure [' . $this->record->identifier . ']',
            ];
        return [
            static::getResource()::getUrl('index') => 'List of structures',
            static::getResource()::getUrl('edit', ['record' => $parent]) => 'Parent structure [' . $parent->identifier . ']',
            static::getResource()::getUrl('edit', ['record' => $this->record]) => 'Ion [' . $this->record->identifier . ']',
        ];
    }

    public function getTitle(): string
    {
        return ($this->record->trashed() ? '(DELETED) ' : "") . 
            "Edit structure [ID:" . $this->record->id . "] - " . ($this->record->nameIdentifier()->first()?->value ?? $this->record->identifier);
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
        return IconEnums::STRUCTURE->value;
    }
}
