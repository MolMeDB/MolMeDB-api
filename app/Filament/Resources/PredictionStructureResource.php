<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Resources\PredictionDatasetResource\RelationManagers\PredictionsRelationManager;
use App\Filament\Resources\PredictionResource\RelationManagers\PredictionDatasetsRelationManager;
use App\Filament\Resources\PredictionStructureResource\Pages;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\PredictionWorkers\Models\PredictionStructure;

class PredictionStructureResource extends Resource
{
    protected static ?string $model = PredictionStructure::class;
    protected static ?string $navigationIcon = IconEnums::STRUCTURE->value;
    protected static ?string $navigationGroup = 'Predictions';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('structure.identifier')
                            ->label('Related structure')
                            ->formatStateUsing(fn (PredictionStructure $record) => $record->structure?->identifier ?? "Unlinked")
                            ->suffixAction(Forms\Components\Actions\Action::make('show_structure')
                                ->icon(IconEnums::VIEW->value)
                                ->url(fn (PredictionStructure $record) => $record->structure ? StructureResource::getUrl('edit', ['record' => $record->structure]) : null)
                                ->openUrlInNewTab()
                            )
                            ->disabled(),
                        Forms\Components\Placeholder::make('canonical_smiles')
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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('structure.identifier')
                    ->label('Identifier')
                    ->default('Unlinked')
                    ->color(fn(PredictionStructure $record) => $record->structure?->identifier ? "primary" : "warning")
                    ->badge(),
                Tables\Columns\TextColumn::make('canonical_smiles')
                    ->label('SMILES')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('predictions_count')
                    ->label('# Predictions')
                    ->counts('predictions'),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn (PredictionStructure $record) => self::getUrl('edit', ['record' => $record]))
                    ->color('warning'),
                Tables\Actions\Action::make('show_structure')
                    ->label('Structure')
                    ->icon(IconEnums::VIEW->value)
                    ->url(fn (?PredictionStructure $record) => $record?->structure ? StructureResource::getUrl('edit', ['record' => $record->structure]) : null)
                    ->disabled(fn (?PredictionStructure $record) => !$record?->structure)
                    ->tooltip(fn (?PredictionStructure $record) => $record?->structure ? 'Show structure details' : 'No linked structure')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPredictionStructures::route('/'),
            'create' => Pages\CreatePredictionStructure::route('/create'),
            'edit' => Pages\EditPredictionStructure::route('/{record}/edit'),
        ];
    }
}
