<?php

return [
    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'sync' => ['driver' => 'sync'],
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => 'jobs',
            'queue' => env('QUEUE_NAME', 'default'),
            // Must exceed ProcessMassImport::$timeout so a long import is
            // never claimed by a second worker while it is still running.
            'retry_after' => 960,
            'after_commit' => true,
        ],
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],
];
