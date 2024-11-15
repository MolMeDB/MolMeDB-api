<?php

namespace App\Filament\Clusters\Categories\Resources\MembraneCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\MembraneCategoryResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMembraneCategory extends CreateRecord
{
    protected static string $resource = MembraneCategoryResource::class;
}
