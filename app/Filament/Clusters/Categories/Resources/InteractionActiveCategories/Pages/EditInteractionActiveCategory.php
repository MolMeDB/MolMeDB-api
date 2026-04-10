<?php

namespace App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\InteractionActiveCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInteractionActiveCategory extends EditRecord
{
    protected static string $resource = InteractionActiveCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
