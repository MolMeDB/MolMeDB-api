<?php

namespace App\Filament\Resources\SubstanceResource\Pages;

use App\Filament\Resources\SubstanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubstances extends ListRecords
{
    protected static string $resource = SubstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
