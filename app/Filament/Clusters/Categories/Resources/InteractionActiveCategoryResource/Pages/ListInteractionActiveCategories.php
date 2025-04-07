<?php

namespace App\Filament\Clusters\Categories\Resources\InteractionActiveCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\InteractionActiveCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInteractionActiveCategories extends ListRecords
{
    protected static string $resource = InteractionActiveCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageMembraneCategories')
                ->label('Manage method categories')
                ->url(InteractionActiveCategoryResource::getUrl('categoryTree'))
        ];
    }
}
