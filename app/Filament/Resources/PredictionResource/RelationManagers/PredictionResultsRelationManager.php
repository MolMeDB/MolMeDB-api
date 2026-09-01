<?php

namespace App\Filament\Resources\PredictionResource\RelationManagers;

use App\Enums\IconEnums;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\PredictionWorkers\Models\PredictionResult;

class PredictionResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'predictionResult';
    protected static ?string $title = 'Results';
    protected static ?string $icon = IconEnums::DOWNLOAD->value;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictionResult()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictionResult()->count() > 0 ? 'success' : 'danger';
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
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_id')
                    ->label('Results form')
                    ->badge()
                    ->formatStateUsing(fn (PredictionResult $record) => $record->file?->exists() ? "File" : "Database"),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon(IconEnums::DOWNLOAD->value)
                    ->url(fn (PredictionResult $record) => $record->file?->hash ? route('predictionResult.download', ['hash' => $record->file->hash]) : null)
                    ->disabled(fn (PredictionResult $record) => !$record->file?->hash),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }
}
