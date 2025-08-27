<?php

namespace App\Filament\Resources\StructureResource\RelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\StructureResource;
use App\Models\Structure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChargesRelationManager extends RelationManager
{
    protected static string $relationship = 'chargedChildren';
    protected static ?string $title = 'Related structures';
    protected static ?string $icon = IconEnums::CHARGED_MOL->value;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord::class == Structure::class && !$ownerRecord->parent()->withTrashed()->first()?->id;
    }

    public function table(Table $table): Table
    {
        static $isParentTrashed = $this->ownerRecord->trashed();

        return StructureResource::getDefaultTable($table)
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->default($isParentTrashed ? 1 : null),
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->openUrlInNewTab()
                    ->url(fn (Structure $record) => StructureResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\RestoreAction::make()
                    ->disabled(fn (Structure $record) => !$record->isRestoreable())
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
