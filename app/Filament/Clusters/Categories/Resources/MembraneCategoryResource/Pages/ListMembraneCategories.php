<?php

namespace App\Filament\Clusters\Categories\Resources\MembraneCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\MembraneCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMembraneCategories extends ListRecords
{
    protected static string $resource = MembraneCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Actions\Action::make('manageMembraneCategories')
                ->label('Manage categories')
                ->url(MembraneCategoryResource::getUrl('categoryTree'))
                // ->icon('heroicon-s-collection'),
        ];
    }
}
