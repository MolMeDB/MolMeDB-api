<?php

namespace App\Filament\Resources\ProteinResource\Pages;

use App\Filament\Resources\ProteinResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProteins extends ListRecords
{
    protected static string $resource = ProteinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
