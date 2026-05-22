<?php

namespace App\Filament\Clusters\Settings\Resources\Filesystems\Pages;

use App\Filament\Clusters\Settings\Resources\Filesystems\FilesystemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFilesystems extends ListRecords
{
    protected static string $resource = FilesystemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make()
            //     ->label('New Service'),
        ];
    }
}
