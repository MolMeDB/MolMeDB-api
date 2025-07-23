<?php

use App\Http\Controllers\MembraneController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\ProteinController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/../modules/References/EuropePMC/Routes/api.php';

Route::middleware(['auth:sanctum'])->get('/api/user', function (Request $request) {
    return $request->user();
});

Route::prefix('/api')->group(function() 
{ 
    Route::get('test', function () {
        return response()->json(['message' => 'OK'], 200);
    });

    Route::prefix('membrane')
        ->controller(MembraneController::class)
        ->group(function() { 
           Route::get('/categories', 'categories'); 
           Route::get('/{membrane}', 'show');
        });

    Route::prefix('method')
        ->controller(MethodController::class)
        ->group(function() { 
           Route::get('/categories', 'categories'); 
           Route::get('/{method}', 'show');
        });

    Route::prefix('protein')
        ->controller(ProteinController::class)
        ->group(function() { 
           Route::get('/categories', 'categories'); 
           Route::get('/{protein}', 'show');
           Route::get('/{protein}/stats', 'stats');
        });
});