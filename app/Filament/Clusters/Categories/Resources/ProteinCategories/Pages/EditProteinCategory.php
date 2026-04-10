<?php

namespace App\Filament\Clusters\Categories\Resources\ProteinCategories\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Categories\Resources\ProteinCategories\ProteinCategoryResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProteinCategory extends EditRecord
{
    protected static string $resource = ProteinCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
             DeleteAction::make()  
                ->before(fn (DeleteAction $action, Category $record) => ProteinCategoryResource::checkIfDeletable($action, $record))
        ];
    }
}
