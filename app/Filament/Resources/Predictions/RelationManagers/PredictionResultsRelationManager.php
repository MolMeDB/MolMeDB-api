<?php

namespace App\Filament\Resources\Predictions\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use App\Enums\IconEnums;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\PredictionWorkers\Models\PredictionResult;

class PredictionResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'predictionResult';
    protected static ?string $title = 'Results';
    protected static string | \BackedEnum | null $icon = IconEnums::DOWNLOAD->value;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictionResult()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictionResult()->count() > 0 ? 'success' : 'danger';
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
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('file_id')
                    ->label('Results form')
                    ->badge()
                    ->formatStateUsing(fn (PredictionResult $record) => $record->file?->exists() ? "File" : "Database"),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon(IconEnums::DOWNLOAD->value)
                    ->url(fn (PredictionResult $record) => $record->file?->hash ? route('predictionResult.download', ['hash' => $record->file->hash]) : null)
                    ->disabled(fn (PredictionResult $record) => !$record->file?->hash),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }
}
