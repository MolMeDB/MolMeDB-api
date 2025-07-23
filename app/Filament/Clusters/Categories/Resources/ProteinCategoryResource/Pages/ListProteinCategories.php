<?php

namespace App\Filament\Clusters\Categories\Resources\ProteinCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\ProteinCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProteinCategories extends ListRecords
{
    protected static string $resource = ProteinCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageProteinCategories')
                ->label('Manage protein categories')
                ->url(ProteinCategoryResource::getUrl('categoryTree'))
        ];
    }
}
