<?php

namespace App\Filament\Clusters\Settings\Resources\Configs\Pages;

use App\Filament\Clusters\Settings\Resources\Configs\ConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConfigs extends ListRecords
{
    protected static string $resource = ConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
