<?php

namespace App\Filament\Resources\SharedRelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\InteractionActiveResource;
use App\Filament\Resources\StructureResource;
use App\Models\Dataset;
use App\Models\InteractionActive;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\Protein;
use App\Models\Publication;
use App\Models\Structure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class InteractionsActiveRelationManager extends RelationManager
{
    protected static string $relationship = 'interactionsActive';
    protected static ?string $title = 'A. interactions';
    protected static ?string $icon = IconEnums::INTERACTIONS->value;

    private function getTableDescriptions() : string
    {
        $deletedParent = $this->ownerRecord->trashed();
        return match($this->ownerRecord::class){
            Structure::class => 'Active interactions assigned to the structure.',
            Dataset::class => 'Active interactions originating from the dataset.',
            Protein::class => 'Active interactions assigned to the protein.',
            Method::class => $deletedParent ? 'Warning! Interactions labeled as "deleted" are hidden. Restore this record to see all assigned interaction.' : 'Active interactions assigned to the method.',
            Membrane::class => $deletedParent ? 'Warning! Interactions labeled as "deleted" are hidden. Restore this record to see all assigned interaction.' : 'Active interactions assigned to the membrane.',
            Publication::class => 'Interactions with current record as PRIMARY reference.',
            default => '' 
        };
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if ($ownerRecord::class == Dataset::class) {
            return match($ownerRecord->type) {
                Dataset::TYPE_ACTIVE => parent::canViewForRecord($ownerRecord, $pageClass),
                default => false
            };
        }
        return true;
    }

    public function table(Table $table): Table
    {
        static $isParentTrashed = $this->ownerRecord->trashed();

        return InteractionActiveResource::table($table)
            ->description($this->getTableDescriptions())
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
                    ->url(fn ($record) => InteractionActiveResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\RestoreAction::make()
                    ->disabled(fn(InteractionActive $record) => !$record->isRestoreable())
            ]);
    }
}
