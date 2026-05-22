<?php

namespace App\Filament\Clusters\Categories\Resources\MethodCategories\Pages;

use App\Filament\Clusters\Categories\Resources\MethodCategories\MethodCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMethodCategory extends CreateRecord
{
    protected static string $resource = MethodCategoryResource::class;
}
