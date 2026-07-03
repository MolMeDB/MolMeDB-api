<?php

namespace App\Filament\Resources\PredictionDatasets;

use App\Enums\IconEnums;
use App\Filament\Resources\PredictionDatasets\Pages\CreatePredictionDataset;
use App\Filament\Resources\PredictionDatasets\Pages\EditPredictionDataset;
use App\Filament\Resources\PredictionDatasets\Pages\ListPredictionDatasets;
use App\Filament\Resources\PredictionDatasets\RelationManagers\PredictionsRelationManager;
use App\Filament\Resources\PredictionDatasets\RelationManagers\StructuresRelationManager;
use App\Models\File;
use App\Models\Membrane;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\PredictionWorkers\Models\Prediction;
use Modules\PredictionWorkers\Models\PredictionDataset;

class PredictionDatasetResource extends Resource
{
    protected static ?string $model = PredictionDataset::class;

    protected static string|\BackedEnum|null $navigationIcon = IconEnums::DATASET->value;

    protected static string|\UnitEnum|null $navigationGroup = 'Predictions';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID')
                    ->hiddenOn('create')
                    ->state(fn (PredictionDataset $record) => $record->id),
                TextEntry::make('user.name')
                    ->label('Owner')
                    ->hiddenOn('create')
                    ->state(fn (PredictionDataset $record) => $record->user?->prettyName),
                TextInput::make('temperature')
                    ->label('Temperature')
                    ->suffix('°C')
                    ->numeric()
                    ->default(37.0)
                    ->minValue(0)
                    ->required(),
                Select::make('method_type')
                    ->label('Method')
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (callable $set, ?string $state) {
                        // $set('membrane_id', null);
                    })
                    ->options(Prediction::remotePredictionMethodOptions()),
                Select::make('membrane_id')
                    ->relationship(
                        name: 'predictionMembrane',
                        titleAttribute: 'abbreviation',
                        modifyQueryUsing: fn (Builder $query) => $query->whereIn(
                            'remote_id',
                            Membrane::query()
                                ->whereHas('files', fn ($q) => $q->where('type', File::TYPE_COSMO_MEMBRANE))
                                ->pluck('id'),
                        ),
                    )
                    ->label('Membrane')
                    ->hint('Only membranes with a COSMO file are shown.')
                    ->hintColor('warning')
                    ->reactive()
                    ->required(),
                Select::make('priority')
                    ->label('Priority')
                    ->options(Prediction::$enum_priorities)
                    ->default(Prediction::PRIORITY_MEDIUM)
                    ->required()
                    ->hint('Changing priority will update all linked predictions to the highest priority across their datasets.')
                    ->hintColor('warning'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('comment')
                    ->default('N/A')
                    ->lineClamp(2),
                ImageColumn::make('user_id')
                    ->label('Created by')
                    ->alignCenter()
                    ->getStateUsing(fn (PredictionDataset $record) => $record->user?->getFilamentAvatarUrl())
                    ->circular()
                    ->sortable()
                    ->imageSize(35)
                    ->tooltip(function (PredictionDataset $record) {
                        return $record->user?->name;
                    }),
                TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        Prediction::PRIORITY_HIGH => 'danger',
                        Prediction::PRIORITY_MEDIUM => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => Prediction::$enum_priorities[$state] ?? 'N/A'),
                TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('method_type')
                    ->label('Method')
                    ->options(Prediction::remotePredictionMethodOptions())
                    ->multiple(),
                SelectFilter::make('priority')
                    ->label('Priority')
                    ->options(Prediction::$enum_priorities)
                    ->multiple(),
                SelectFilter::make('membrane_id')
                    ->label('Membrane')
                    ->relationship('predictionMembrane', 'abbreviation')
                    ->multiple()
                    ->searchable(),
                SelectFilter::make('user_id')
                    ->label('Owner')
                    ->options(fn () => User::query()->pluck('name', 'id'))
                    ->multiple()
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->color('warning'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StructuresRelationManager::class,
            PredictionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPredictionDatasets::route('/'),
            'create' => CreatePredictionDataset::route('/create'),
            'edit' => EditPredictionDataset::route('/{record}/edit'),
        ];
    }
}
