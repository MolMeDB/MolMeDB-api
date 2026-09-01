<?php

namespace App\Filament\Clusters\Settings\Resources\SshCredentialResource\Pages;

use App\Filament\Clusters\Settings\Resources\SshCredentialResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class CreateSshCredential extends CreateRecord
{
    protected static string $resource = SshCredentialResource::class;

    public function getTitle(): string | Htmlable
    {
        return "Create new SSH Credential";
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::user()->id;
        return $data;
    }
}
