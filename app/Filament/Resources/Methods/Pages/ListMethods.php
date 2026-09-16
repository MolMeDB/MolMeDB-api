<?php

namespace App\Filament\Resources\Methods\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Methods\MethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMethods extends ListRecords
{
    protected static string $resource = MethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
