<?php

namespace App\Filament\Resources\MembraneResource\Pages;

use App\Filament\Resources\MembraneResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMembrane extends CreateRecord
{
    protected static string $resource = MembraneResource::class;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $data['user_id'] = auth()->id();
    //     return $data;
    // }
}
