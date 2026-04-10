<?php

namespace App\Filament\Clusters\Categories\Resources\MethodCategories\Pages;

use Filament\Actions\Action;
use App\Filament\Clusters\Categories\Resources\MethodCategories\MethodCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMethodCategories extends ListRecords
{
    protected static string $resource = MethodCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageMembraneCategories')
                ->label('Manage method categories')
                ->url(MethodCategoryResource::getUrl('categoryTree'))
        ];
    }
}
