<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\UploadQueue;
use Illuminate\Support\Facades\Storage;

class ExportUploadQueueController extends Controller
{
    /**
     * Export raw uploaded file from the queue
     * 
     * @param UploadQueue $record
     */
    public function raw(UploadQueue $record)
    {
        $file = $record->file;

        if(!$file || !$file->existsOnDisk('private'))
        {
            abort(404, 'File not found');
        }

        return Storage::disk('private')->download($file->path);
    }

    /**
     * Export uploaded file from the queue - parsed
     * 
     * @param UploadQueue $record
     */
    public function index(UploadQueue $record)
    {
        //TODO
        abort(404, 'Not implemented');

        // $file = $record->file;

        // if(!$file || !$file->existsOnDisk('private'))
        // {
        //     abort(404, 'File not found');
        // }

        // return Storage::disk('private')->download($file->path);
    }
}
