<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/../modules/EuropePMC/Routes/api.php';

Route::middleware(['auth:sanctum'])->get('/api/user', function (Request $request) {
    return $request->user();
});

Route::prefix('/api')->group(function() 
{ 
    Route::get('test', function () {
        return response()->json(['message' => 'OK'], 200);
    });
});