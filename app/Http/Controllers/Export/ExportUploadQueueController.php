<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\UploadQueue;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();

        if(!Storage::disk($file->storage)->exists($file->path))
        {
            abort(404, 'File not found');
        }

        if(!$user->hasAdminRole() && $record->user_id !== $user->id)
        {
            abort(401, 'Unathenticated');   
        }

        return Storage::disk($file->storage)->download($file->path);
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
