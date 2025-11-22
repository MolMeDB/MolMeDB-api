<?php
 
namespace App\Filament\Clusters\Settings\Resources\FilesystemResource\Pages;

use App\Filament\Clusters\Settings\Resources\FilesystemResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;
 
class FilesystemActivities extends ListActivities
{
    protected static string $resource = FilesystemResource::class;
}