<?php

use App\Http\Controllers\MembraneController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\ProteinController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\StatsController;
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

    Route::prefix("publication")
        ->controller(PublicationController::class)
        ->group(function() {
            Route::get('/', 'index');
            Route::get('/{publication}', 'show');
            Route::get('/{publication}/stats', 'stats');
        });

    Route::prefix('stats')
        ->controller(StatsController::class)
        ->group(function() {
           Route::get('/', 'index');
           Route::get('/publications', 'publications');
        });
});