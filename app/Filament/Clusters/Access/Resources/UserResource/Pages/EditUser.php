<?php

namespace App\Filament\Clusters\Access\Resources\UserResource\Pages;

use App\Filament\Clusters\Access\Resources\UserResource;
use App\Notifications\WelcomeUser;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // $user = $this->record;
        // $user->notifyNow(new WelcomeUser($user));
    }
}
