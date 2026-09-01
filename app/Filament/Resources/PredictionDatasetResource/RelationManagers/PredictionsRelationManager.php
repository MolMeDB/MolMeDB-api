<?php

namespace App\Filament\Resources\PredictionDatasetResource\RelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\PredictionResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\PredictionWorkers\Models\Prediction;

class PredictionsRelationManager extends RelationManager
{
    protected static string $relationship = 'predictions';
    protected static ?string $icon = IconEnums::PREDICTION->value;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictions()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictions()->count() > 0 ? 'primary' : 'danger';
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
            ->columns(
                PredictionResource::table($table)->getColumns()
            )
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('download_results')
                    ->label('Download all results')
                    // ->action()
                    ->icon(IconEnums::DOWNLOAD->value)
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn (Prediction $record) => PredictionResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }
}
