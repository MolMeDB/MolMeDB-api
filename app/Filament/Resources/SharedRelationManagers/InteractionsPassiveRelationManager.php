<?php

namespace App\Filament\Resources\SharedRelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\InteractionPassiveResource;
use App\Filament\Resources\StructureResource;
use App\Models\Dataset;
use App\Models\InteractionPassive;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\Publication;
use App\Models\Structure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InteractionsPassiveRelationManager extends RelationManager
{
    protected static string $relationship = 'interactionsPassive';
    protected static ?string $title = 'P. interactions';
    protected static ?string $icon = IconEnums::INTERACTIONS->value;

    private function getTableDescriptions() : string
    {
        $deletedParent = $this->ownerRecord->trashed();
        return match($this->ownerRecord::class){
            Structure::class => 'Passive interactions assigned to the structure.',
            Dataset::class => 'Passive interactions originating from the dataset.',
            Method::class => $deletedParent ? 'Warning! Interactions labeled as "deleted" are hidden. Restore this record to see all assigned interaction.' : 'Passive interactions assigned to the method.',
            Membrane::class => $deletedParent ? 'Warning! Interactions labeled as "deleted" are hidden. Restore this record to see all assigned interaction.' : 'Passive interactions assigned to the membrane.',
            Publication::class => 'Interactions with current record as PRIMARY reference.',
            default => '' 
        };
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if ($ownerRecord::class == Dataset::class) {
            return match($ownerRecord->type) {
                Dataset::TYPE_PASSIVE => parent::canViewForRecord($ownerRecord, $pageClass),
                Dataset::TYPE_PASSIVE_INTERNAL_COSMO => parent::canViewForRecord($ownerRecord, $pageClass),
                default => false
            };
        }
        return true;
    }

    public function table(Table $table): Table
    {
        static $isParentTrashed = $this->ownerRecord->trashed();

        return InteractionPassiveResource::table($table)
            ->description($this->getTableDescriptions())
            ->query(null)
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->default($isParentTrashed ? 1 : null),
            ])
            ->actions([
                ...($this->ownerRecord::class === Structure::class ? [] : [
                    Tables\Actions\Action::make('compound_detail')
                    ->label('Structure')
                    ->icon(IconEnums::VIEW->value)
                    ->url(fn ($record) => StructureResource::getUrl('edit', ['record' => $record->structure])),
                ]),
                Tables\Actions\EditAction::make()
                    ->color('warning')
                    ->url(fn ($record) => InteractionPassiveResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\RestoreAction::make()
                    ->disabled(fn(InteractionPassive $record) => !$record->isRestoreable())
            ]);
    }
}
