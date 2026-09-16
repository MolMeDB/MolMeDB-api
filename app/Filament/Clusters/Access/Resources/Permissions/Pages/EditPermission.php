<?php

namespace App\Filament\Clusters\Access\Resources\Permissions\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Access\Resources\Permissions\PermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPermission extends EditRecord
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
