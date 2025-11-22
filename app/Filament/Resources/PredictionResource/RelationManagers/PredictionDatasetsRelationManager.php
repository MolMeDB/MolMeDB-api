<?php

namespace App\Filament\Resources\PredictionResource\RelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\PredictionDatasetResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\PredictionWorkers\Models\PredictionDataset;

class PredictionDatasetsRelationManager extends RelationManager
{
    protected static string $relationship = 'predictionDatasets';
    protected static ?string $icon = IconEnums::DATASET->value;

    protected static ?string $title = 'Datasets';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictionDatasets()->count();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
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
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('comment')
                    ->lineClamp(2),
                Tables\Columns\ImageColumn::make('user_id')
                    ->label('Created by')
                    ->alignCenter()
                    ->getStateUsing(fn (PredictionDataset $record) => $record->user?->getFilamentAvatarUrl())
                    ->circular()
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
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->url(fn (PredictionDataset $record) => PredictionDatasetResource::getUrl('edit', ['record' => $record]))
                    ->label('Edit')
                    ->icon(IconEnums::EDIT->value)
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }
}
