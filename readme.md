## Logger for Aws Cloud Watch

### Breaking Change for version 1.0

When this package started it started as a listener for log event and would only work with another driver.
This package would listen to log events and just log extra to cloud watch but after `1.0` it works as a custom driver.
So, you MUST add a `LOG_CHANNEL` for logging in your config for this to work.

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
            'via' => \Pagevamp\Logger::class,
        ],
]
```

And set the log channel in your environment variable to `LOG_CHANNEL` to `cloudwatch`.

If the role of your AWS EC2 instance has access to Cloudwatch logs, `CLOUDWATCH_LOG_KEY` and `CLOUDWATCH_LOG_SECRET` need not be defined in your `.env` file.

### Contribution

I have added a `pre-commit` hook to run `php-cs-fixer` whenever you make a commit. To enable this run `sh hooks.sh`.

