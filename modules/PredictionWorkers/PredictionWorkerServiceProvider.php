<?php

namespace Modules\PredictionWorkers;

use Illuminate\Support\ServiceProvider;
use Modules\PredictionWorkers\Services\RemotePrediction\RemotePredictionClient;

class PredictionWorkerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RemotePredictionClient::class);
    }

    public function boot(): void
    {
        //
    }
}
