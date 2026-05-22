<?php

namespace App\Filament\Resources\Proteins\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Proteins\ProteinResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProteins extends ListRecords
{
    protected static string $resource = ProteinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
