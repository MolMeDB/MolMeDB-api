<?php

namespace App\Filament\Resources\Publications\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Publications\PublicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPublications extends ListRecords
{
    protected static string $resource = PublicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
