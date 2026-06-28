<?php

use Illuminate\Support\Facades\Route;

Route::prefix('epmc')->group(function () {
    Route::get('/test', function () {
        return response()->json(['message' => 'OK'], 200);
    });
});
