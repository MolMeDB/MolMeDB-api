<?php

namespace App\Filament\Resources\MembraneResource\Pages;

use App\Filament\Resources\MembraneResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMembrane extends EditRecord
{
    protected static string $resource = MembraneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
