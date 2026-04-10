<?php

namespace App\Filament\Resources\Predictions;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Predictions\Pages\ListPredictions;
use App\Filament\Resources\Predictions\Pages\CreatePrediction;
use App\Filament\Resources\Predictions\Pages\EditPrediction;
use App\Enums\IconEnums;
use App\Filament\Resources\PredictionResource\Pages;
use App\Filament\Resources\Predictions\RelationManagers\PredictionDatasetsRelationManager;
use App\Filament\Resources\Predictions\RelationManagers\PredictionResultsRelationManager;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\PredictionWorkers\Models\Prediction;

class PredictionResource extends Resource
{
    protected static ?string $model = Prediction::class;
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::PREDICTION->value;
    protected static string | \UnitEnum | null $navigationGroup = 'Predictions';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('id')
                    ->label('ID')
                    ->hiddenOn('create')
                    ->content(fn (Prediction $record) => $record->id),
                Placeholder::make('structure_id')
                    ->label('Structure canonical smiles')
                    ->hiddenOn('create')
                    ->content(fn (Prediction $record) => $record->predictionStructure->canonical_smiles),
                Placeholder::make('step')
                    ->label('Current step')
                    ->hiddenOn('create')
                    ->content(fn (Prediction $record) => $record->enumStep($record->step)),
                Placeholder::make('state')
                    ->label('Step state')
                    ->hiddenOn('create')
                    ->content(fn (Prediction $record) => $record->enumState($record->state)),
                TextInput::make('temperature')
                    ->label('Temperature')
                    ->suffix('°C')
                    ->numeric()
                    ->disabled()
                    ->default(37.0)
                    ->hint('Settings must be changed for the whole dataset.')
                    ->minValue(0)
                    ->hintColor('warning')
                    ->columnSpanFull()
                    ->required(),
                Select::make('method_type')
                    ->label('Method')
                    ->live()
                    ->disabled()
                    ->hint('Settings must be changed for the whole dataset.')
                    ->hintColor('warning')
                    ->required()
                    ->afterStateUpdated(function (callable $set, ?string $state) {
                        $set('membrane_id', null);
                    })
                    ->options(Prediction::$enum_methods),
                Select::make('membrane_id')
                    ->disabled()
                    ->relationship(
                        name: 'predictionMembrane', 
                        titleAttribute: 'abbreviation',
                    )
                    ->label('Membrane')
                    ->hint('Settings must be changed for the whole dataset.')
                    ->hintColor('warning')
                    ->reactive()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('predictionStructure.id')
                    ->label('Structure'),
                // Tables\Columns\TextColumn::make('predictionDatasets_count')
                //     ->label("# Dataset")
                //     ->counts('predictionDatasets')
                //     ->sortable()
                //     ->searchable()
                //     ->badge(),
                TextColumn::make('method_type')
                    ->label('Method')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => Prediction::enumMethod($state)),
                TextColumn::make('predictionMembrane.abbreviation')
                    ->label('Membrane')
                    ->sortable()
                    ->badge(),
                TextColumn::make('state')
                    ->label('State')
                    ->sortable()
                    ->description(fn (Prediction $record) => $record->step ? Prediction::enumStep($record->step) : null)
                    ->formatStateUsing(fn (string $state) => Prediction::enumState($state)),
                TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => Prediction::enumPriority($state))

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->color('warning'),
                // Tables\Actions\Action::make('showStructure')
                //     ->label('Structure')
                //     ->icon(IconEnums::VIEW->value)
                //     ->url(fn ($record) => StructureResource::getUrl('edit', ['record' => $record->predictionStructure->structure]))
                //     ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PredictionDatasetsRelationManager::class,
            PredictionResultsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPredictions::route('/'),
            'create' => CreatePrediction::route('/create'),
            'edit' => EditPrediction::route('/{record}/edit'),
        ];
    }
}
