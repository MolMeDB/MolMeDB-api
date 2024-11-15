<?php

namespace App\Filament\Clusters\Categories\Resources\MethodCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\MethodCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMethodCategory extends CreateRecord
{
    protected static string $resource = MethodCategoryResource::class;
}
