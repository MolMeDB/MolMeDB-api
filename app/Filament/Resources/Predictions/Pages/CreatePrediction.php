<?php

namespace App\Filament\Resources\Predictions\Pages;

use App\Filament\Resources\Predictions\PredictionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePrediction extends CreateRecord
{
    protected static string $resource = PredictionResource::class;
}
