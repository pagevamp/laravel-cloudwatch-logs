<?php

return [
    'cloudwatch' => [
        'driver' => 'custom',
        'name' => env('CLOUDWATCH_LOG_NAME', ''),
        'region' => env('CLOUDWATCH_LOG_REGION', ''),
        'credentials' => [
            'key' => env('CLOUDWATCH_LOG_KEY', ''),
            'secret' => env('CLOUDWATCH_LOG_SECRET', ''),
        ],
        'stream_name' => env('CLOUDWATCH_LOG_STREAM_NAME', 'laravel_app'),
        'retention' => env('CLOUDWATCH_LOG_RETENTION_DAYS', 14),
        'group_name' => env('CLOUDWATCH_LOG_GROUP_NAME', 'laravel_app'),
        'version' => env('CLOUDWATCH_LOG_VERSION', 'latest'),
        'batch_size' => env('CLOUDWATCH_LOG_BATCH_SIZE', 10000),
        'rps_limit' => env('CLOUDWATCH_LOG_RPS_LIMIT', 0),
        'formatter' => function ($configs) {
            return new \Monolog\Formatter\LineFormatter(
                '%channel%: %level_name%: %message% %context% %extra%',
                null,
                false,
                true
            );
        },
        'via' => \Pagevamp\Logger::class
    ],
];
