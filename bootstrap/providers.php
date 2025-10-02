<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\FortifyServiceProvider::class,
    Modules\PredictionWorkers\PredictionWorkerServiceProvider::class
];
