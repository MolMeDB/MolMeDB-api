<?php

namespace App\Filament\Resources\Predictions;

use App\Enums\IconEnums;
use App\Filament\Resources\Predictions\Pages\CreatePrediction;
use App\Filament\Resources\Predictions\Pages\EditPrediction;
use App\Filament\Resources\Predictions\Pages\ListPredictions;
use App\Filament\Resources\Predictions\RelationManagers\PredictionDatasetsRelationManager;
use App\Filament\Resources\Predictions\RelationManagers\PredictionResultsRelationManager;
use App\Filament\Resources\PredictionStructures\PredictionStructureResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Modules\PredictionWorkers\Models\Prediction;

class PredictionResource extends Resource
{
    protected static ?string $model = Prediction::class;

    protected static string|\BackedEnum|null $navigationIcon = IconEnums::PREDICTION->value;

    protected static string|\UnitEnum|null $navigationGroup = 'Predictions';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID')
                    ->hiddenOn('create')
                    ->state(fn (Prediction $record) => $record->id),
                TextEntry::make('structure_id')
                    ->label('Structure canonical smiles')
                    ->hiddenOn('create')
                    ->state(fn (Prediction $record) => $record->predictionStructure->canonical_smiles)
                    ->url(fn (Prediction $record) => PredictionStructureResource::getUrl('edit', ['record' => $record->predictionStructure]))
                    ->openUrlInNewTab(),
                SchemaView::make('filament.predictions.status-panel')
                    ->hiddenOn('create')
                    ->columnSpanFull(),
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
                    ->options(Prediction::remotePredictionMethodOptions()),
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
                Section::make('Logs')
                    ->hiddenOn('create')
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        SchemaView::make('filament.predictions.logs')
                            ->columnSpanFull(),
                    ]),
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
                    ->badge()
                    ->color(fn (Prediction $record): string => $record->isRemotePaused() ? 'gray' : match ((int) $record->state) {
                        Prediction::STATE_FINISHED => 'success',
                        Prediction::STATE_RUNNING => 'warning',
                        Prediction::STATE_ERROR => 'danger',
                        Prediction::STATE_REMOVE => 'danger',
                        Prediction::STATE_STOPPED => 'gray',
                        default => 'info',
                    })
                    ->description(fn (Prediction $record) => $record->isRemotePaused()
                        ? $record->remote_pause_reason
                        : ($record->step ? Prediction::enumStep($record->step) : null))
                    ->formatStateUsing(fn ($state, Prediction $record) => $record->effectiveStateLabel()),
                TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => Prediction::enumPriority($state)),

            ])
            ->filters([
                SelectFilter::make('state')
                    ->label('State')
                    ->options(Prediction::$enum_states)
                    ->multiple(),
                TernaryFilter::make('remote_paused_at')
                    ->label('Paused')
                    ->nullable(),
                SelectFilter::make('step')
                    ->label('Step')
                    ->options(Prediction::$enum_steps)
                    ->multiple(),
                SelectFilter::make('method_type')
                    ->label('Method')
                    ->options(Prediction::remotePredictionMethodOptions())
                    ->multiple(),
                SelectFilter::make('membrane_id')
                    ->label('Membrane')
                    ->relationship('predictionMembrane', 'abbreviation')
                    ->multiple()
                    ->searchable(),
                SelectFilter::make('priority')
                    ->label('Priority')
                    ->options(Prediction::$enum_priorities)
                    ->multiple(),
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
            PredictionResultsRelationManager::class,
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
