<?php

namespace App\Filament\Resources\Proteins\Pages;

use App\Filament\Resources\Proteins\ProteinResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProtein extends CreateRecord
{
    protected static string $resource = ProteinResource::class;
}
