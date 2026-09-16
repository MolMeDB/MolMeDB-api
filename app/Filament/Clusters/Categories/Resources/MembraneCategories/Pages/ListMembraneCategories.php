<?php

namespace App\Filament\Clusters\Categories\Resources\MembraneCategories\Pages;

use Filament\Actions\Action;
use App\Filament\Clusters\Categories\Resources\MembraneCategories\MembraneCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMembraneCategories extends ListRecords
{
    protected static string $resource = MembraneCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Action::make('manageMembraneCategories')
                ->label('Manage membrane categories')
                ->url(MembraneCategoryResource::getUrl('categoryTree'))
                // ->icon('heroicon-s-collection'),
        ];
    }
}
