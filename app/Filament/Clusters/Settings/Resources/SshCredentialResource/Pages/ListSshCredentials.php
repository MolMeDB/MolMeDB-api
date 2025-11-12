<?php

namespace App\Filament\Clusters\Settings\Resources\SshCredentialResource\Pages;

use App\Filament\Clusters\Settings\Resources\SshCredentialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSshCredentials extends ListRecords
{
    protected static string $resource = SshCredentialResource::class;
    
    protected static ?string $title = 'SSH Credentials';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New SSH Credential'),
        ];
    }
}
