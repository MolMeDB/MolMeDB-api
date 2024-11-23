<?php

namespace App\Filament\Clusters\Categories\Resources\MethodCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\MethodCategoryResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMethodCategory extends EditRecord
{
    protected static string $resource = MethodCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()  
                ->before(fn (Actions\DeleteAction $action, Category $record) => MethodCategoryResource::checkIfDeletable($action, $record))
        ];
    }
}
