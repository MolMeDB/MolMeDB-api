<?php

namespace App\Filament\Resources\Datasets\Pages;

use App\Enums\IconEnums;
use App\Filament\Resources\Datasets\DatasetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDatasets extends ListRecords
{
    protected static string $resource = DatasetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make()
            //     ->label('Upload new dataset')
            //     ->icon(IconEnums::UPLOAD->value),
        ];
    }
}
