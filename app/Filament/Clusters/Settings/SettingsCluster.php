<?php

namespace App\Filament\Clusters\Settings;

use App\Enums\IconEnums;
use Filament\Clusters\Cluster;

class SettingsCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::SETTINGS->value;
    protected static ?int $navigationSort = 50;

}
