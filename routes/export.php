<?php

use App\Http\Controllers\Export;
use App\Models\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::prefix('/export')->group(function() 
{ 
    Route::get('/upload-queue/raw/{record}', [Export\ExportUploadQueueController::class, 'raw'])
        ->middleware(['auth', 'throttle:6,1'])
        ->name('export.upload-queue.raw');

    Route::get('/upload-queue/{record}', [Export\ExportUploadQueueController::class, 'index'])
        ->middleware(['auth', 'throttle:6,1'])
        ->name('export.upload-queue');
});

Route::get('/download/public/{hash}', function (string $hash) {
    $file = File::where('hash', $hash)->first();
    if(!$file || !Storage::disk('public')->exists($file->path))
    {
        abort(404);
    }
    return response()->download(Storage::disk('public')->path($file->path), $file->downloadName());
})->middleware('throttle:6,1')
    ->withoutMiddleware('auth')
    ->name('public.download');