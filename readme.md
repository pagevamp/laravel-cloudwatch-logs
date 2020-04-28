## Logger for Aws Cloud Watch

### Installation

`composer require pagevamp/laravel-cloudwatch-logs`

### Example

You can use laravel's default `\Log` class to use this

`\Log::info('user logged in', ['id' => 123, 'name' => 'Naren']);`

### Config

Config for logging is defined at `config/logging.php`. Add `cloudwatch` to the `channels` array

```
'channels' =>  [
    'cloudwatch' => [
            'driver' => 'custom',
            'name' => env('CLOUDWATCH_LOG_NAME', ''),
            'region' => env('CLOUDWATCH_LOG_REGION', ''),
            'credentials' => [
                'key' => env('CLOUDWATCH_LOG_KEY', ''),
                'secret' => env('CLOUDWATCH_LOG_SECRET', '')
            ],
            'stream_name' => env('CLOUDWATCH_LOG_STREAM_NAME', 'laravel_app'),
            'retention' => env('CLOUDWATCH_LOG_RETENTION_DAYS', 14),
            'group_name' => env('CLOUDWATCH_LOG_GROUP_NAME', 'laravel_app'),
            'version' => env('CLOUDWATCH_LOG_VERSION', 'latest'),
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'disabled' => env('DISABLE_CLOUDWATCH_LOG', false),
        ],
]
```

Add correct values to keys in your `.env` file. And it should work. 

If the role of your AWS EC2 instance has access to Cloudwatch logs, `CLOUDWATCH_LOG_KEY` and `CLOUDWATCH_LOG_SECRET` need not be defined in your `.env` file.

### Add To Project
 
#### Laravel 5.5 or Higher

This package uses laravel's [Package discovery](https://laravel.com/docs/5.6/packages#package-discovery). To disable this package by default you can add `DISABLE_CLOUDWATCH_LOG=true` to you local `.env` file and this package will be disabled.

#### Laravel 5.4 or Lower

Add to the `providers` array in `config/app.php`:

```
Pagevamp\Providers\CloudWatchServiceProvider::class
```

### Concept

This package relies on laravel's listener for log events. This package DOES NOT replace the default logging, instead adds additional log to AWS CLoud Watch. Hence you do not have to change the default log driver to make this work.

### Contribution

I have added a `pre-commit` hook to run `php-cs-fixer` whenever you make a commit. To enable this run `sh hooks.sh`.

