<?php

namespace App\Filament\Resources\MembraneResource\Pages;

use App\Filament\Resources\MembraneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMembranes extends ListRecords
{
    protected static string $resource = MembraneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
