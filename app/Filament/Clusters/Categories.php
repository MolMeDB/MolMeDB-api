<?php

namespace App\Filament\Clusters;

use App\Enums\IconEnums;
use Filament\Clusters\Cluster;

class Categories extends Cluster
{
    protected static ?string $navigationIcon = IconEnums::CATEGORIES->value;
}
