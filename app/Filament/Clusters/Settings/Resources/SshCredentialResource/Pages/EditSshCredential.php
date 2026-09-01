<?php

namespace App\Filament\Clusters\Settings\Resources\SshCredentialResource\Pages;

use App\Enums\IconEnums;
use App\Filament\Clusters\Settings\Resources\SshCredentialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSshCredential extends EditRecord
{
    protected static string $resource = SshCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('history')
                ->url(fn ($record) => SshCredentialResource::getUrl('activities', ['record' => $record]))
                ->color('warning')
                ->icon(IconEnums::ACTIVITY->value),
            Actions\DeleteAction::make(),
        ];
    }
}
