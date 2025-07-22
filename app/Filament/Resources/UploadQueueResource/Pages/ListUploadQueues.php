<?php

namespace App\Filament\Resources\UploadQueueResource\Pages;

use App\Enums\IconEnums;
use App\Filament\Resources\UploadQueueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUploadQueues extends ListRecords
{
    protected static string $resource = UploadQueueResource::class;
    protected static ?string $title = 'Upload interactions';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add new dataset')
                ->icon(IconEnums::ADD->value)
                ->color('primary')
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            UploadQueueResource::getUrl('index') => 'Upload interactions',
            'List'
        ];
    }
}
