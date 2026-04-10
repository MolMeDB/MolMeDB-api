<?php

namespace App\Filament\Clusters\Settings\Resources\SshCredentials\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Enums\IconEnums;
use App\Filament\Clusters\Settings\Resources\SshCredentials\SshCredentialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSshCredential extends EditRecord
{
    protected static string $resource = SshCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('history')
                ->url(fn ($record) => SshCredentialResource::getUrl('activities', ['record' => $record]))
                ->color('warning')
                ->icon(IconEnums::ACTIVITY->value),
            DeleteAction::make(),
        ];
    }
}
