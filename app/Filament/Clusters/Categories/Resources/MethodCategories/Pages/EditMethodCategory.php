<?php

namespace App\Filament\Clusters\Categories\Resources\MethodCategories\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Categories\Resources\MethodCategories\MethodCategoryResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMethodCategory extends EditRecord
{
    protected static string $resource = MethodCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()  
                ->before(fn (DeleteAction $action, Category $record) => MethodCategoryResource::checkIfDeletable($action, $record))
        ];
    }
}
