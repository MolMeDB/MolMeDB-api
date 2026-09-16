<?php

namespace App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\Pages;

use Filament\Actions\Action;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\InteractionActiveCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInteractionActiveCategories extends ListRecords
{
    protected static string $resource = InteractionActiveCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageMembraneCategories')
                ->label('Manage method categories')
                ->url(InteractionActiveCategoryResource::getUrl('categoryTree'))
        ];
    }
}
