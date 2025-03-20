<?php

namespace App\Filament\Resources\SharedRelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\DatasetResource;
use App\Models\Dataset;
use App\Models\Membrane;
use App\Models\Method;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DatasetsRelationManager extends RelationManager
{
    protected static string $relationship = 'datasets';
    protected static ?string $icon = IconEnums::DATASET->value;

    public function form(Form $form): Form
    {
        return DatasetResource::form($form);
    }

    public function table(Table $table): Table
    {
        static $isParentTrashed = $this->ownerRecord->trashed();

        return DatasetResource::table($table)
            ->description($this->getTableDescriptions())
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(Dataset::enumType()),
                Tables\Filters\TrashedFilter::make()
                    ->default($isParentTrashed ? 1 : null),
            ])
            ->actions([
                // ...$table->getActions(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
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
