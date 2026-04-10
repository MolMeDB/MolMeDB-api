<?php

namespace App\Filament\Resources\SharedRelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use App\Enums\IconEnums;
use App\Filament\Resources\Datasets\DatasetResource;
use App\Models\Dataset;
use App\Models\Membrane;
use App\Models\Method;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DatasetsRelationManager extends RelationManager
{
    protected static string $relationship = 'datasets';
    protected static string | \BackedEnum | null $icon = IconEnums::DATASET->value;

    public function form(Schema $schema): Schema
    {
        return DatasetResource::form($schema);
    }

    public function table(Table $table): Table
    {
        static $isParentTrashed = $this->ownerRecord->trashed();

        return DatasetResource::table($table)
            ->description($this->getTableDescriptions())
            ->query(null)
            ->filters([
                SelectFilter::make('type')
                    ->options(Dataset::enumType()),
                TrashedFilter::make()
                    ->default($isParentTrashed ? 1 : null),
            ])
            ->recordActions([
                // ...$table->getActions(),
                ViewAction::make(),
                EditAction::make()
                    ->url(fn (Dataset $record) => DatasetResource::getUrl('edit', ['record' => $record]))
                    ->icon(IconEnums::NEWTAB->value)
                    ->openUrlInNewTab()
            ]);
    }

    private function getTableDescriptions() : string 
    {
        return match($this->ownerRecord::class)
        {
            Method::class => 'Datasets directly related to this method.',
            Membrane::class => 'Datasets directly related to this membrane.',
            default => 'Attached datasets.'
        };
    }
}
