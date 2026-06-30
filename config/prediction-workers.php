<?php

return [
    'structure_validation' => [
        'max_atoms' => 120,
        'allowed_elements' => ['C', 'H', 'O', 'N', 'P', 'S', 'F', 'Cl', 'Br', 'I'],
        'single_connected_molecule' => true,
    ],
    'remote' => [
        'manager_secret' => env('REMOTE_PREDICTION_MANAGER_SECRET'),
        'send_method_parameter' => (bool) env('REMOTE_PREDICTION_SEND_METHOD_PARAMETER', false),
        'connect_timeout' => (int) env('REMOTE_PREDICTION_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('REMOTE_PREDICTION_TIMEOUT', 60),
        'download_timeout' => (int) env('REMOTE_PREDICTION_DOWNLOAD_TIMEOUT', 300),
        'retry_delays' => [200, 500, 1000],
        'worker' => [
            'queue' => env('REMOTE_PREDICTION_QUEUE', 'predictions'),
            'status_interval_seconds' => (int) env('REMOTE_PREDICTION_STATUS_INTERVAL_SECONDS', 300),
            'max_active' => (int) env('REMOTE_PREDICTION_MAX_ACTIVE', 100),
            'max_status_requests_per_minute' => (int) env('REMOTE_PREDICTION_MAX_STATUS_REQUESTS_PER_MINUTE', 30),
            'max_result_downloads' => (int) env('REMOTE_PREDICTION_MAX_RESULT_DOWNLOADS', 5),
            'max_submissions' => (int) env('REMOTE_PREDICTION_MAX_SUBMISSIONS', 5),
            'events_limit' => (int) env('REMOTE_PREDICTION_EVENTS_LIMIT', 100),
            'admin_bulk_limit' => (int) env('REMOTE_PREDICTION_ADMIN_BULK_LIMIT', 20),
        ],
        'methods' => [
            'cosmoperm' => [
                'remote_method' => 'cosmoperm',
                'label' => 'CosmoPerm',
            ],
        ],
    ],
];
