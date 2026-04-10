<?php

namespace App\Filament\Clusters\Categories\Resources\ProteinCategories\Pages;

use Filament\Actions\Action;
use App\Filament\Clusters\Categories\Resources\ProteinCategories\ProteinCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProteinCategories extends ListRecords
{
    protected static string $resource = ProteinCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageProteinCategories')
                ->label('Manage protein categories')
                ->url(ProteinCategoryResource::getUrl('categoryTree'))
        ];
    }
}
