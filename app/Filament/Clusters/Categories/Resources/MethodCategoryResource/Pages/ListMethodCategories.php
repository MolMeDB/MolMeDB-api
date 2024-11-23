<?php

namespace App\Filament\Clusters\Categories\Resources\MethodCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\MethodCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMethodCategories extends ListRecords
{
    protected static string $resource = MethodCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageMembraneCategories')
                ->label('Manage method categories')
                ->url(MethodCategoryResource::getUrl('categoryTree'))
        ];
    }
}
