<?php

namespace App\Filament\Resources\PredictionStructures;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\PredictionStructures\Pages\ListPredictionStructures;
use App\Filament\Resources\PredictionStructures\Pages\CreatePredictionStructure;
use App\Filament\Resources\PredictionStructures\Pages\EditPredictionStructure;
use App\Enums\IconEnums;
use App\Filament\Resources\PredictionDatasets\RelationManagers\PredictionsRelationManager;
use App\Filament\Resources\Predictions\RelationManagers\PredictionDatasetsRelationManager;
use App\Filament\Resources\PredictionStructureResource\Pages;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\PredictionWorkers\Models\PredictionStructure;

class PredictionStructureResource extends Resource
{
    protected static ?string $model = PredictionStructure::class;
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::STRUCTURE->value;
    protected static string | \UnitEnum | null $navigationGroup = 'Predictions';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('structure.identifier')
                            ->label('Related structure')
                            ->formatStateUsing(fn (PredictionStructure $record) => $record->structure?->identifier ?? "Unlinked")
                            ->suffixAction(Action::make('show_structure')
                                ->icon(IconEnums::VIEW->value)
                                ->url(fn (PredictionStructure $record) => $record->structure ? StructureResource::getUrl('edit', ['record' => $record->structure]) : null)
                                ->openUrlInNewTab()
                            )
                            ->disabled(),
                        Placeholder::make('canonical_smiles')
                            ->label('SMILES')
                            ->content(fn (PredictionStructure $record) => $record->canonical_smiles),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Unlinked records will be automaticaly linked to the structures in the future CRON run.')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('structure.identifier')
                    ->label('Identifier')
                    ->default('Unlinked')
                    ->color(fn(PredictionStructure $record) => $record->structure?->identifier ? "primary" : "warning")
                    ->badge(),
                TextColumn::make('canonical_smiles')
                    ->label('SMILES')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('predictions_count')
                    ->label('# Predictions')
                    ->counts('predictions'),
                TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (PredictionStructure $record) => self::getUrl('edit', ['record' => $record]))
                    ->color('warning'),
                Action::make('show_structure')
                    ->label('Structure')
                    ->icon(IconEnums::VIEW->value)
                    ->url(fn (?PredictionStructure $record) => $record?->structure ? StructureResource::getUrl('edit', ['record' => $record->structure]) : null)
                    ->disabled(fn (?PredictionStructure $record) => !$record?->structure)
                    ->tooltip(fn (?PredictionStructure $record) => $record?->structure ? 'Show structure details' : 'No linked structure')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PredictionDatasetsRelationManager::class,
            PredictionsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPredictionStructures::route('/'),
            'create' => CreatePredictionStructure::route('/create'),
            'edit' => EditPredictionStructure::route('/{record}/edit'),
        ];
    }
}
