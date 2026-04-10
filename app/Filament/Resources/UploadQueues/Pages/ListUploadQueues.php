<?php

namespace App\Filament\Resources\UploadQueues\Pages;

use Filament\Actions\CreateAction;
use App\Enums\IconEnums;
use App\Filament\Resources\UploadQueues\UploadQueueResource;
use App\Models\UploadQueue;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListUploadQueues extends ListRecords
{
    protected static string $resource = UploadQueueResource::class;
    protected static ?string $title = 'Upload interactions';

    protected function getHeaderActions(): array
    {
        if(!UploadQueue::canBeAddedNewRecords())
        {
            Notification::make()
                ->title('RdKit not connected')
                ->body('Cannot establish connection to RdKit server. Uploading new datasets is disabled.')
                ->warning()
                ->persistent()
                ->send();
            return [];
        }

        return [
            CreateAction::make()
                ->label('Upload dataset')
                ->icon(IconEnums::UPLOAD->value)
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
