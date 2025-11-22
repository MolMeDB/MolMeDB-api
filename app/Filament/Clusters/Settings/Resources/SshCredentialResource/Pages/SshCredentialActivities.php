<?php
 
namespace App\Filament\Clusters\Settings\Resources\SshCredentialResource\Pages;

use App\Filament\Clusters\Settings\Resources\SshCredentialResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;
 
class SshCredentialActivities extends ListActivities
{
    protected static string $resource = SshCredentialResource::class;
}