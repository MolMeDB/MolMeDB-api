<?php

namespace App\Filament\Resources\SubstanceResource\Pages;

use App\Filament\Resources\SubstanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubstance extends EditRecord
{
    protected static string $resource = SubstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
