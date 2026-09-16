<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\UploadQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportUploadQueueController extends Controller
{
    /**
     * Export raw uploaded file from the queue
     */
    public function raw(Request $request, UploadQueue $record)
    {
        $file = $record->file;
        $guestToken = $request->query('guest_token');
        $guestToken = is_string($guestToken) && trim($guestToken) !== '' ? trim($guestToken) : null;

        if (! Storage::disk($file->storage)->exists($file->path)) {
            abort(404, 'File not found');
        }

        if (! $record->isAccessibleBy($request->user(), $guestToken)) {
            abort(401, 'Unathenticated');
        }

        return Storage::disk($file->storage)->download($file->path);
    }

    /**
     * Export uploaded file from the queue - parsed
     */
    public function index(UploadQueue $record)
    {
        // TODO
        abort(404, 'Not implemented');

        // $file = $record->file;

        // if(!$file || !$file->existsOnDisk('private'))
        // {
        //     abort(404, 'File not found');
        // }

        // return Storage::disk('private')->download($file->path);
    }
}
