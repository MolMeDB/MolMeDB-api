<?php

namespace App\Filament\Clusters\Access\Resources\RoleResource\Pages;

use App\Enums\IconEnums;
use App\Enums\RoleEnums;
use App\Filament\Clusters\Access\Resources\RoleResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($record): bool => $record->id > count(RoleEnums::cases())),
            Actions\Action::make('reloadPermissions')
                ->label('Reload permission cache')
                ->color('success')
                ->icon(IconEnums::RELOAD->value)
                ->action(function () {
                    app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
                    // Add alert
                    Notification::make()
                        ->title('Permissions cache flushed')
                        ->success()
                        ->send();
                })
        ];
    }

    protected function afterSave(): void
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

}
