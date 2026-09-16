<?php

namespace App\Filament\Resources\UploadQueues\Pages;

use App\Filament\Resources\UploadQueues\UploadQueueResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListUploadQueues extends ListRecords
{
    protected static string $resource = UploadQueueResource::class;

    protected static ?string $title = 'Upload interactions';

    protected function getHeaderActions(): array
    {
        Notification::make()
            ->title('Manual admin upload is disabled')
            ->body('Upload requests are accepted only from frontend Laboratory page.')
            ->warning()
            ->send();

        return [];
    }

    public function getBreadcrumbs(): array
    {
        return [
            UploadQueueResource::getUrl('index') => 'Upload interactions',
            'List',
        ];
    }
}
