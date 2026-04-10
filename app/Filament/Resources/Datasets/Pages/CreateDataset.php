<?php

namespace App\Filament\Resources\Datasets\Pages;

use App\Filament\Resources\Datasets\DatasetResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDataset extends CreateRecord
{
    protected static string $resource = DatasetResource::class;
}
