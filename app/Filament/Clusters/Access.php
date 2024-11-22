<?php

namespace App\Filament\Clusters;

use App\Enums\IconEnums;
use Filament\Clusters\Cluster;

class Access extends Cluster
{
    protected static ?string $navigationIcon = IconEnums::ACCESS->value;
    protected static ?int $navigationSort = 4;

}
