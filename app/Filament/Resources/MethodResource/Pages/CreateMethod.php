<?php

namespace App\Filament\Resources\MethodResource\Pages;

use App\Filament\Resources\MethodResource;
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
