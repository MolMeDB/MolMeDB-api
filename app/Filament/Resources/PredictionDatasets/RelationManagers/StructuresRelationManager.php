<?php

namespace App\Filament\Resources\PredictionDatasets\RelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\PredictionStructures\PredictionStructureResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\PredictionWorkers\Models\PredictionStructure;

class StructuresRelationManager extends RelationManager
{
    protected static string $relationship = 'predictionStructures';

    protected static string|\BackedEnum|null $icon = IconEnums::STRUCTURE->value;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) PredictionStructure::query()
            ->whereIn('id', $ownerRecord->predictions()->select('structure_id'))
            ->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return PredictionStructure::query()
            ->whereIn('id', $ownerRecord->predictions()->select('structure_id'))
            ->exists() ? 'primary' : 'danger';
    }

    protected function getTableQuery(): Builder
    {
        return PredictionStructure::query()
            ->whereIn('id', $this->getOwnerRecord()->predictions()->select('structure_id'));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return PredictionStructureResource::table($table);
    }
}
