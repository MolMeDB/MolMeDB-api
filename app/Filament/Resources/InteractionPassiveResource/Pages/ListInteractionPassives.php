<?php

namespace App\Filament\Resources\InteractionPassiveResource\Pages;

use App\Filament\Resources\InteractionPassiveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInteractionPassives extends ListRecords
{
    protected static string $resource = InteractionPassiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
