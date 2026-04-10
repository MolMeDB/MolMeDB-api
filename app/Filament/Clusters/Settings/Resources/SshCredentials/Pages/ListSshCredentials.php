<?php

namespace App\Filament\Clusters\Settings\Resources\SshCredentials\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\Settings\Resources\SshCredentials\SshCredentialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSshCredentials extends ListRecords
{
    protected static string $resource = SshCredentialResource::class;
    
    protected static ?string $title = 'SSH Credentials';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New SSH Credential'),
        ];
    }
}
