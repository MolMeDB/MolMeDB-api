<?php

namespace App\Filament\Resources\Methods\Pages;

use App\Filament\Resources\Methods\MethodResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMethod extends CreateRecord
{
    protected static string $resource = MethodResource::class;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     // dd($data);
    //     // $data['user_id'] = auth()->id();
    //     // return $data;
    // }
}
