<?php

namespace App\Filament\Resources\Membranes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Membranes\MembraneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMembranes extends ListRecords
{
    protected static string $resource = MembraneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
