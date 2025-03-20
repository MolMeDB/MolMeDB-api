<?php

namespace App\Filament\Resources\SharedRelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\MembraneResource;
use App\Models\Membrane;
use App\Models\Publication;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MembranesRelationManager extends RelationManager
{
    protected static string $relationship = 'membranes';
    protected static ?string $icon = IconEnums::MEMBRANE->value;

    public function form(Form $form): Form
    {
        return MembraneResource::form($form);
    }

    public function table(Table $table): Table
    {
        static $isParentTrashed = $this->ownerRecord->trashed();

        return MembraneResource::table($table)
            ->description($this->getTableDescriptions())
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->default($isParentTrashed ? 1 : null),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->url(fn (Membrane $record) => MembraneResource::getUrl('edit', ['record' => $record]))
                    ->icon(IconEnums::NEWTAB->value)
                    ->openUrlInNewTab()
            ]);
    }

    private function getTableDescriptions() : string 
    {
        return match($this->ownerRecord::class)
        {
            Publication::class => 'Membranes originating from the publication.',
            default => 'Attached membranes.'
        };
    }
}
