<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('/api')->group(function() 
{ 
    Route::prefix('epmc')->group(function() 
    { 
        Route::get('/test', function () {
            return response()->json(['message' => 'OK'], 200);
        });
        
        // Implement endpoints for EuropePMC - task: 8697k72h7
    });
});