<?php
 
namespace App\Filament\Clusters\Settings\Resources\SshCredentials\Pages;

use App\Filament\Clusters\Settings\Resources\SshCredentials\SshCredentialResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;
 
class SshCredentialActivities extends ListActivities
{
    protected static string $resource = SshCredentialResource::class;
}