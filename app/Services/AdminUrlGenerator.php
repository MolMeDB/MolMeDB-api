<?php

namespace App\Services;

use App\Filament\Resources\UploadQueues\UploadQueueResource;
use App\Models\UploadQueue;

class AdminUrlGenerator
{
    public function uploadQueueEditUrl(UploadQueue $record): string
    {
        return $this->absoluteUrl(
            UploadQueueResource::getUrl(
                'edit',
                ['record' => $record],
                isAbsolute: false,
            ),
        );
    }

    private function absoluteUrl(string $path): string
    {
        return rtrim((string) config('app.admin_url', config('app.url')), '/').'/'.ltrim($path, '/');
    }
}
