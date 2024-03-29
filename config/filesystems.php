<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3-asup' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID', '103784_asup_media'),
            'secret' => env('AWS_SECRET_ACCESS_KEY', '5t=BUw{S%X'),
            'region' => env('AWS_DEFAULT_REGION', 'ru-1'),
            'bucket' => env('AWS_BUCKET', 'Asup_media'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT', 'https://s3.storage.selcloud.ru'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],
        's3-iri' => [
            'driver' => 's3',
            'key' => 'client-izhevsk',
            'secret' => 'hfU6f93HSuVzdEnAWutyKTC',
            'region' => 'us-east-1',
            'bucket' => 'izhevsk-inbox-to-cdp-inline',
            'url' => '',
            'endpoint' => 'https://s3.inline-dmp.ru',
            'use_path_style_endpoint' => true,
            'throw' => false,
        ],
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
