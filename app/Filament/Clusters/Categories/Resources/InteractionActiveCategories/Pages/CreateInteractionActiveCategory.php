<?php

namespace App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\Pages;

use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\InteractionActiveCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInteractionActiveCategory extends CreateRecord
{
    protected static string $resource = InteractionActiveCategoryResource::class;
}
