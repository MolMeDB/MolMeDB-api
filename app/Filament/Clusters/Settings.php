<?php

namespace App\Filament\Clusters;

use App\Enums\IconEnums;
use Filament\Clusters\Cluster;

class Settings extends Cluster
{
    protected static ?string $navigationIcon = IconEnums::SETTINGS->value;
    protected static ?int $navigationSort = 50;

}
