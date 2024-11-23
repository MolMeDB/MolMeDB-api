<?php

namespace App\Filament\Clusters\Categories\Resources\MembraneCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\MembraneCategoryResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMembraneCategory extends EditRecord
{
    protected static string $resource = MembraneCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()  
                ->before(fn (Actions\DeleteAction $action, Category $record) => MembraneCategoryResource::checkIfDeletable($action, $record))
        ];
    }
}
