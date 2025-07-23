<?php

namespace App\Filament\Clusters\Categories\Resources\ProteinCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\ProteinCategoryResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProteinCategory extends EditRecord
{
    protected static string $resource = ProteinCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
             Actions\DeleteAction::make()  
                ->before(fn (Actions\DeleteAction $action, Category $record) => ProteinCategoryResource::checkIfDeletable($action, $record))
        ];
    }
}
