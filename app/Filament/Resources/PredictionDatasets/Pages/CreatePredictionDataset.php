<?php

namespace App\Filament\Resources\PredictionDatasets\Pages;

use App\Filament\Resources\PredictionDatasets\PredictionDatasetResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePredictionDataset extends CreateRecord
{
    protected static string $resource = PredictionDatasetResource::class;
}
