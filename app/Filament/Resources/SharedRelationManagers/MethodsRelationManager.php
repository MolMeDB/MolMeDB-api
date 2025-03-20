<?php

namespace App\Filament\Resources\SharedRelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\MethodResource;
use App\Models\Method;
use App\Models\Publication;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MethodsRelationManager extends RelationManager
{
    protected static string $relationship = 'methods';
    protected static ?string $icon = IconEnums::METHOD->value;

    public function form(Form $form): Form
    {
        return MethodResource::form($form);
    }

    public function table(Table $table): Table
    {
        static $isParentTrashed = $this->ownerRecord->trashed();

        return MethodResource::table($table)
            ->description($this->getTableDescriptions())
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->default($isParentTrashed ? 1 : null),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->url(fn (Method $record) => MethodResource::getUrl('edit', ['record' => $record]))
                    ->icon(IconEnums::NEWTAB->value)
                    ->openUrlInNewTab()
            ]);
    }

    private function getTableDescriptions() : string 
    {
        return match($this->ownerRecord::class)
        {
            Publication::class => 'Methods originating from the publication.',
            default => 'Attached methods.'
        };
    }
}
