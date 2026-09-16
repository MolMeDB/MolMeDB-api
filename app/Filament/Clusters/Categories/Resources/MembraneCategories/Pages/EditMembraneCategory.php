<?php

namespace App\Filament\Clusters\Categories\Resources\MembraneCategories\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Categories\Resources\MembraneCategories\MembraneCategoryResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMembraneCategory extends EditRecord
{
    protected static string $resource = MembraneCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()  
                ->before(fn (DeleteAction $action, Category $record) => MembraneCategoryResource::checkIfDeletable($action, $record))
        ];
    }
}
