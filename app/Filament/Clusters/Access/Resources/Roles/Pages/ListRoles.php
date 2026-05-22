<?php

namespace App\Filament\Clusters\Access\Resources\Roles\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Spatie\Permission\PermissionRegistrar;
use App\Enums\IconEnums;
use App\Filament\Clusters\Access\Resources\Roles\RoleResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('reloadPermissions')
                ->label('Reload permission cache')
                ->color('success')
                ->icon(IconEnums::RELOAD->value)
                ->action(function () {
                    app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
                    // Add alert
                    Notification::make()
                        ->title('Permissions cache flushed')
                        ->success()
                        ->send();
                })
        ];
    }
}
