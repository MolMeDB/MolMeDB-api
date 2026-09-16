<?php

namespace App\Filament\Resources\Predictions\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use App\Enums\IconEnums;
use App\Filament\Resources\PredictionDatasets\PredictionDatasetResource;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\PredictionWorkers\Models\PredictionDataset;

class PredictionDatasetsRelationManager extends RelationManager
{
    protected static string $relationship = 'predictionDatasets';
    protected static string | \BackedEnum | null $icon = IconEnums::DATASET->value;

    protected static ?string $title = 'Datasets';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictionDatasets()->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->description('List of datasets in which this prediction request was present.')
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('comment')
                    ->lineClamp(2),
                ImageColumn::make('user_id')
                    ->label('Created by')
                    ->alignCenter()
                    ->getStateUsing(fn (PredictionDataset $record) => $record->user?->getFilamentAvatarUrl())
                    ->circular()
                    ->size(35)
                    ->tooltip(function (PredictionDataset $record) {
                        return $record->user?->name;
                    }),
                TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->recordActions([
                Action::make('edit')
                    ->url(fn (PredictionDataset $record) => PredictionDatasetResource::getUrl('edit', ['record' => $record]))
                    ->label('Edit')
                    ->icon(IconEnums::EDIT->value)
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }
}
