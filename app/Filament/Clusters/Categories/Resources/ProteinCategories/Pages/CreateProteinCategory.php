<?php

namespace App\Filament\Clusters\Categories\Resources\ProteinCategories\Pages;

use App\Filament\Clusters\Categories\Resources\ProteinCategories\ProteinCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProteinCategory extends CreateRecord
{
    protected static string $resource = ProteinCategoryResource::class;
}
