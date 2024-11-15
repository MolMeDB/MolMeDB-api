<?php

namespace App\Filament\Clusters\Categories\Resources\MembraneCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\MembraneCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMembraneCategory extends EditRecord
{
    protected static string $resource = MembraneCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
