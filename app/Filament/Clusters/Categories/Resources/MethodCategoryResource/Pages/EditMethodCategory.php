<?php

namespace App\Filament\Clusters\Categories\Resources\MethodCategoryResource\Pages;

use App\Filament\Clusters\Categories\Resources\MethodCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMethodCategory extends EditRecord
{
    protected static string $resource = MethodCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
