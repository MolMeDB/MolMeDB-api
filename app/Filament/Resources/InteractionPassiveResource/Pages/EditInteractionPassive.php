<?php

namespace App\Filament\Resources\InteractionPassiveResource\Pages;

use App\Enums\IconEnums;
use App\Filament\Resources\DatasetResource;
use App\Filament\Resources\InteractionPassiveResource;
use App\Filament\Resources\StructureResource;
use App\Models\InteractionPassive;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditInteractionPassive extends EditRecord
{
    protected static string $resource = InteractionPassiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make()
                ->icon(IconEnums::RESTORE->value)
                ->modalHeading('Restore interaction?')
                ->modalDescription('Do you want to restore the interaction record?')
                ->disabled(fn (InteractionPassive $record) => !$record->isRestoreable())
        ];
    }

    public function getBreadcrumbs(): array
    {
        $dataset = $this->record->dataset()->withTrashed()->first();
        $structure = $this->record->structure()->withTrashed()->first();

        return [
            DatasetResource::getUrl('edit', ['record' => $dataset]) => "Dataset [" . ($dataset?->name ?? $dataset?->id) . "]",
            StructureResource::getUrl('edit', ['record' => $structure]) => "Structure [" . ($structure->identifier ?? $structure->id) . "]",
            static::getResource()::getUrl('edit', ['record' => $this->record]) => 'Active interaction [' . $this->record->id . ']',
        ];
    }

    public function getTitle(): string
    {
        return ($this->record->trashed() ? '(DELETED) ' : "") .  
            "Edit passive interaction [ID:" . $this->record->id . "]";
    }

    public function getSubheading(): string|Htmlable|null
    {
        return "Remember, that modifying this record leads to inconsistent data and should be done with caution!";
    }

    // public function hasCombinedRelationManagerTabsWithContent(): bool
    // {
    //     return true;
    // }
}
