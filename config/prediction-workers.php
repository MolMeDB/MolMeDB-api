<?php

return [
    'remote' => [
        'manager_secret' => env('REMOTE_PREDICTION_MANAGER_SECRET'),
        'send_method_parameter' => (bool) env('REMOTE_PREDICTION_SEND_METHOD_PARAMETER', false),
        'connect_timeout' => (int) env('REMOTE_PREDICTION_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('REMOTE_PREDICTION_TIMEOUT', 60),
        'download_timeout' => (int) env('REMOTE_PREDICTION_DOWNLOAD_TIMEOUT', 300),
        'retry_delays' => [200, 500, 1000],
        'worker' => [
            'status_interval_seconds' => (int) env('REMOTE_PREDICTION_STATUS_INTERVAL_SECONDS', 300),
            'max_status_updates' => (int) env('REMOTE_PREDICTION_MAX_STATUS_UPDATES', 20),
            'max_submissions' => (int) env('REMOTE_PREDICTION_MAX_SUBMISSIONS', 5),
            'events_limit' => (int) env('REMOTE_PREDICTION_EVENTS_LIMIT', 100),
        ],
        'methods' => [
            'cosmoperm' => [
                'remote_method' => 'cosmoperm',
                'label' => 'CosmoPerm',
            ],
        ],
    ],
];
