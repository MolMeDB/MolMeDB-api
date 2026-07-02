<?php

use App\Models\UploadQueue;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
require __DIR__.'/export.php';

if (app()->environment('local', 'development')) {
    Route::get('/test', function () {
        return view('livewire.upload-queue-configure-wrapper', ['record' => UploadQueue::find(120)]);
    });
}
