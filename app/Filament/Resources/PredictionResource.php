<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Resources\PredictionResource\Pages;
use App\Filament\Resources\PredictionResource\RelationManagers\PredictionDatasetsRelationManager;
use App\Filament\Resources\PredictionResource\RelationManagers\PredictionResultsRelationManager;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\PredictionWorkers\Models\Prediction;

class PredictionResource extends Resource
{
    protected static ?string $model = Prediction::class;
    protected static ?string $navigationIcon = IconEnums::PREDICTION->value;
    protected static ?string $navigationGroup = 'Predictions';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('id')
                    ->label('ID')
                    ->hiddenOn('create')
                    ->content(fn (Prediction $record) => $record->id),
                Forms\Components\Placeholder::make('structure_id')
                    ->label('Structure canonical smiles')
                    ->hiddenOn('create')
                    ->content(fn (Prediction $record) => $record->predictionStructure->canonical_smiles),
                Forms\Components\Placeholder::make('step')
                    ->label('Current step')
                    ->hiddenOn('create')
                    ->content(fn (Prediction $record) => $record->enumStep($record->step)),
                Forms\Components\Placeholder::make('state')
                    ->label('Step state')
                    ->hiddenOn('create')
                    ->content(fn (Prediction $record) => $record->enumState($record->state)),
                Forms\Components\TextInput::make('temperature')
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
                Forms\Components\Select::make('method_type')
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
                Forms\Components\Select::make('membrane_id')
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
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('predictionStructure.id')
                    ->label('Structure'),
                // Tables\Columns\TextColumn::make('predictionDatasets_count')
                //     ->label("# Dataset")
                //     ->counts('predictionDatasets')
                //     ->sortable()
                //     ->searchable()
                //     ->badge(),
                Tables\Columns\TextColumn::make('method_type')
                    ->label('Method')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => Prediction::enumMethod($state)),
                Tables\Columns\TextColumn::make('predictionMembrane.abbreviation')
                    ->label('Membrane')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('state')
                    ->label('State')
                    ->sortable()
                    ->description(fn (Prediction $record) => $record->step ? Prediction::enumStep($record->step) : null)
                    ->formatStateUsing(fn (string $state) => Prediction::enumState($state)),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => Prediction::enumPriority($state))

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                // Tables\Actions\Action::make('showStructure')
                //     ->label('Structure')
                //     ->icon(IconEnums::VIEW->value)
                //     ->url(fn ($record) => StructureResource::getUrl('edit', ['record' => $record->predictionStructure->structure]))
                //     ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListPredictions::route('/'),
            'create' => Pages\CreatePrediction::route('/create'),
            'edit' => Pages\EditPrediction::route('/{record}/edit'),
        ];
    }
}
