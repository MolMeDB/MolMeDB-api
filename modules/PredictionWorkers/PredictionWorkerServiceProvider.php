<?php 
namespace Modules\PredictionWorkers;

class PredictionWorkerServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}