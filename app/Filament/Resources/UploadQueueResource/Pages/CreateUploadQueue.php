<?php

namespace App\Filament\Resources\UploadQueueResource\Pages;

use App\Filament\Resources\UploadQueueResource;
use App\Models\File;
use App\Models\UploadQueue;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateUploadQueue extends CreateRecord
{
    protected static string $resource = UploadQueueResource::class;

    protected static ?string $title = 'Upload new interactions';

    public function getBreadcrumbs(): array
    {
        return [
            UploadQueueResource::getUrl('index') => 'Upload interactions',
            'Add'
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['membrane_id']);
        unset($data['method_id']);

        $data['user_id'] = Auth::user()->id;
        $data['state'] = UploadQueue::STATE_UPLOADED;

        // Save file record
        $file = new File();
        $file->path = $data['path'];
        $file->name = basename($file->path);
        $file->type = $data['type'] == UploadQueue::TYPE_ACTIVE_DATASET ? File::TYPE_UPLOAD_ACTIVE : File::TYPE_UPLOAD_PASSIVE;

        $file->save();

        unset($data['path']);
        $data['file_id'] = $file->id;

        return $data;
    }
}
