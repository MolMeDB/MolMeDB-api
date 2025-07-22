<?php

use App\Http\Controllers\Export;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('/export')->group(function() 
{ 
    Route::get('/upload-queue/raw/{record}', [Export\ExportUploadQueueController::class, 'raw'])
        ->middleware(['auth', 'throttle:6,1'])
        ->name('export.upload-queue.raw');

    Route::get('/upload-queue/{record}', [Export\ExportUploadQueueController::class, 'index'])
        ->middleware(['auth', 'throttle:6,1'])
        ->name('export.upload-queue');
});