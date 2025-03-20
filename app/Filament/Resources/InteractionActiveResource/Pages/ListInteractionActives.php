<?php

namespace App\Filament\Resources\InteractionActiveResource\Pages;

use App\Filament\Resources\InteractionActiveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInteractionActives extends ListRecords
{
    protected static string $resource = InteractionActiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
