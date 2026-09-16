<?php

namespace App\Filament\Clusters\Categories\Resources\MembraneCategories\Pages;

use App\Filament\Clusters\Categories\Resources\MembraneCategories\MembraneCategoryResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMembraneCategory extends CreateRecord
{
    protected static string $resource = MembraneCategoryResource::class;
}
