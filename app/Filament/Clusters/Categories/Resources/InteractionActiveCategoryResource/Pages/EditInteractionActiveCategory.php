<?php

namespace App\Filament\Clusters\Categories\Resources\InteractionActiveCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\InteractionActiveCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInteractionActiveCategory extends EditRecord
{
    protected static string $resource = InteractionActiveCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
