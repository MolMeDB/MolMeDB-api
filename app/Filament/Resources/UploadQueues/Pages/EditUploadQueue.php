<?php

namespace App\Filament\Resources\UploadQueues\Pages;

use App\Filament\Resources\UploadQueues\UploadQueueResource;
use App\Livewire\UploadQueueLogsTable;
use Filament\Resources\Pages\EditRecord;

class EditUploadQueue extends EditRecord
{
    protected static string $resource = UploadQueueResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['membrane_id']);
        unset($data['method_id']);
        unset($data['path']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return UploadQueueResource::uploadActions();
    }

    protected function getFooterWidgets(): array
    {
        return [
            UploadQueueLogsTable::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }
}
