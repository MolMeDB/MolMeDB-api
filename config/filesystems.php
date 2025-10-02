<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'private' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'cosmo_runner' => [
            'driver' => 'sftp',
            'host' => env('COSMO_SFTP_HOST'),
            'username' => env('COSMO_SFTP_USERNAME'),
            'privateKey' => env('COSMO_SFTP_PRIVATE_KEY'),
            'port' => intval(env('COSMO_SFTP_PORT', 22)),
            'root' => env('COSMO_SFTP_ROOT_PATH', '~/backups'),
            'timeout' => 30,
            'visibility' => 'public',
            'directory_visibility' => 'public'
        ],

        'backups' => [
            'driver' => 'sftp',
            'host' => env('BACKUP_SFTP_HOST'),
            'username' => env('BACKUP_SFTP_USERNAME'),
            'privateKey' => env('BACKUP_SFTP_PRIVATE_KEY'),
            'port' => intval(env('BACKUP_SFTP_PORT', 22)),
            'root' => env('BACKUP_SFTP_ROOT_PATH', '~/backups'),
            'timeout' => 30,
            'visibility' => 'public',
            'directory_visibility' => 'public'
        ],

        'remote-uploads' => [
            'driver' => 'scoped',
            'disk' => 'backups',
            'prefix' => 'Uploads'
        ],

        'remote-structures' => [
            'driver' => 'scoped',
            'disk' => 'backups',
            'prefix' => 'Structures'
        ],

        'remote-predictions' => [
            'driver' => 'scoped',
            'disk' => 'backups',
            'prefix' => 'Structures/Predictions'
        ],

        // 's3' => [
        //     'driver' => 's3',
        //     'key' => env('AWS_ACCESS_KEY_ID'),
        //     'secret' => env('AWS_SECRET_ACCESS_KEY'),
        //     'region' => env('AWS_DEFAULT_REGION'),
        //     'bucket' => env('AWS_BUCKET'),
        //     'url' => env('AWS_URL'),
        //     'endpoint' => env('AWS_ENDPOINT'),
        //     'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        //     'throw' => false,
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
