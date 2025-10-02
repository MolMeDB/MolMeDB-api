<?php

namespace App\Filament\Resources\PredictionDatasetResource\RelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\PredictionStructureResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\PredictionWorkers\Models\PredictionStructure;

class StructuresRelationManager extends RelationManager
{
    protected static string $relationship = 'predictionStructures';
    protected static ?string $icon = IconEnums::STRUCTURE->value;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictionStructures()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->predictionStructures()->count() > 0 ? 'primary' : 'danger';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
            ]);
    }

    public function table(Table $table): Table
    {
        return PredictionStructureResource::table($table);
    }
}