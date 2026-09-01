<?php

namespace App\Filament\Clusters\Settings\Resources\FilesystemResource\Pages;

use App\Filament\Clusters\Settings\Resources\FilesystemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFilesystem extends ListRecords
{
    protected static string $resource = FilesystemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
