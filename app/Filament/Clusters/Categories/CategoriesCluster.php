<?php

namespace App\Filament\Clusters\Categories;

use App\Enums\IconEnums;
use Filament\Clusters\Cluster;

class CategoriesCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::CATEGORIES->value;
}
