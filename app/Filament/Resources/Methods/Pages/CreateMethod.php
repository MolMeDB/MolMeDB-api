<?php

namespace App\Filament\Resources\Methods\Pages;

use App\Filament\Resources\Methods\MethodResource;
use App\ValueObjects\MethodParameters;
use Filament\Resources\Pages\CreateRecord;

class CreateMethod extends CreateRecord
{
    protected static string $resource = MethodResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['parameters'] = new MethodParameters($data['parameters'] ?? []);

        return $data;
    }
}
