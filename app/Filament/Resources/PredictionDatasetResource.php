<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Resources\PredictionDatasetResource\Pages;
use App\Filament\Resources\PredictionDatasetResource\RelationManagers;
use App\Filament\Resources\PredictionDatasetResource\RelationManagers\PredictionsRelationManager;
use App\Filament\Resources\PredictionDatasetResource\RelationManagers\StructuresRelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\PredictionWorkers\Models\Prediction;
use Modules\PredictionWorkers\Models\PredictionDataset;

class PredictionDatasetResource extends Resource
{
    protected static ?string $model = PredictionDataset::class;
    protected static ?string $navigationIcon = IconEnums::DATASET->value;
    protected static ?string $navigationGroup = 'Predictions';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('id')
                    ->label('ID')
                    ->hiddenOn('create')
                    ->content(fn (PredictionDataset $record) => $record->id),
                Forms\Components\Placeholder::make('user.name')
                    ->label('Owner')
                    ->hiddenOn('create')
                    ->content(fn (PredictionDataset $record) => $record->user?->prettyName),
                Forms\Components\TextInput::make('temperature')
                    ->label('Temperature')
                    ->suffix('°C')
                    ->numeric()
                    ->default(37.0)
                    ->minValue(0)
                    ->required(),
                Forms\Components\Select::make('priority')
                    ->label('Priority')
                    ->required()
                    ->options(Prediction::$enum_priorities),
                Forms\Components\Select::make('method_type')
                    ->label('Method')
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (callable $set, ?string $state) {
                        // $set('membrane_id', null);
                    })
                    ->options(Prediction::$enum_methods),
                Forms\Components\Select::make('membrane_id')
                    ->relationship(
                        name: 'predictionMembrane', 
                        titleAttribute: 'abbreviation',
                        modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query, Get $get) => 
                        match($get('method_type')) {
                            Prediction::METHOD_COSMOMIC => $query->whereHas('file', fn ($q) => 
                                $q->where('type', \App\Models\File::TYPE_COSMO_MEMBRANE)
                            ),
                            Prediction::METHOD_COSMOPERM => $query->whereHas('file', fn ($q) => 
                                $q->where('type', \App\Models\File::TYPE_COSMO_MEMBRANE)
                            ),
                            default => $query
                        })
                    ->label('Membrane')
                    ->hint('Only available options for selected method are shown.')
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
                Tables\Columns\TextColumn::make('comment')
                    ->default('N/A')
                    ->lineClamp(2),
                Tables\Columns\ImageColumn::make('user_id')
                    ->label('Created by')
                    ->alignCenter()
                    ->getStateUsing(fn (PredictionDataset $record) => $record->user?->getFilamentAvatarUrl())
                    ->circular()
                    ->sortable()
                    ->size(35)
                    ->tooltip(function (PredictionDataset $record) {
                        return $record->user?->name;
                    }),
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
                    ->color('warning'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StructuresRelationManager::class,
            PredictionsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPredictionDatasets::route('/'),
            'create' => Pages\CreatePredictionDataset::route('/create'),
            'edit' => Pages\EditPredictionDataset::route('/{record}/edit'),
        ];
    }
}
