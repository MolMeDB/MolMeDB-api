<?php

namespace App\Filament\Resources\PredictionDatasets\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use App\Enums\IconEnums;
use App\Filament\Resources\Predictions\PredictionResource;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\PredictionWorkers\Models\Prediction;

class PredictionsRelationManager extends RelationManager
{
    protected static string $relationship = 'predictions';
    protected static string | \BackedEnum | null $icon = IconEnums::PREDICTION->value;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictions()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictions()->count() > 0 ? 'primary' : 'danger';
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
            ->columns(
                PredictionResource::table($table)->getColumns()
            )
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('download_results')
                    ->label('Download all results')
                    // ->action()
                    ->icon(IconEnums::DOWNLOAD->value)
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Prediction $record) => PredictionResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }
}
