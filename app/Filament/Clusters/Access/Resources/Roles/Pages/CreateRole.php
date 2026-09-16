<?php

namespace App\Filament\Clusters\Access\Resources\Roles\Pages;

use Spatie\Permission\PermissionRegistrar;
use App\Filament\Clusters\Access\Resources\Roles\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function afterCreate(): void
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

}
