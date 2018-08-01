<?php

namespace Pagevamp\Providers;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Illuminate\Support\ServiceProvider;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Pagevamp\Exceptions\IncompleteCloudWatchConfig;

class CloudWatchServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $app = $this->app;

        // Listen to log messages.
        $app['log']->listen(function () use ($app) {
            $args = func_get_args();
            $level = $args[0];
            $message = $args[1];
            $context = $args[2];

            if ($message instanceof \ErrorException) {
                return $this->getLogger()->log($level, $message, $context);
            }
            if ($app['cloudwatch.logger'] instanceof Logger) {
                $app['cloudwatch.logger']->log($level, $message, $context);
            }
        });
    }

    /**
     * Code "inspired" from here
     * https://aws.amazon.com/blogs/developer/php-application-logging-with-amazon-cloudwatch-logs-and-monolog
     * Laravel installation mentioned here did not work but PHP with Monolog worked, hence this package.
     */
    public function register()
    {
        $this->app->singleton('cloudwatch.logger', function () {
            $cwClient = new CloudWatchLogsClient($this->getCredentials());
            $loggingConfig = config('logging');
            $cwStreamNameApp = $loggingConfig['stream_name'];
            $cwRetentionDays = $loggingConfig['retention'];
            $logHandler = new CloudWatch($cwClient, 'chs-api', $cwStreamNameApp, $cwRetentionDays, 10000, ['application' => $loggingConfig['tag_name']]);
            $logger = new Logger($loggingConfig['name']);
            $formatter = new LineFormatter('%channel%: %level_name%: %message% %context% %extra%', null, false, true);
            $logHandler->setFormatter($formatter);
            $logger->pushHandler($logHandler);

            return $logger;
        });
    }

    /**
     * This is the way config should be defined in config/logging.php
     * in key cloudwatch.
     *
     *  'cloudwatch' => [
     * 'name' => env('CLOUDWATCH_LOG_NAME', ''),
     * 'region' => env('CLOUDWATCH_LOG_REGION', ''),
     * 'key' => env('CLOUDWATCH_LOG_KEY', ''),
     * 'secret' => env('CLOUDWATCH_LOG_SECRET', ''),
     * 'stream_name' => env('CLOUDWATCH_LOG_STREAM_NAME', 'laravel_app'),
     * 'tag_name' => env('CLOUDWATCH_LOG_TAG_NAME', 'laravel_app'),
     * 'retention' => env('CLOUDWATCH_LOG_RETENTION_DAYS', 14),
     * 'group_name' => env('CLOUDWATCH_LOG_GROUP_NAME', 'laravel_app'),
     *          'version' => env('CLOUDWATCH_LOG_VERSION', 'latest'),
     * ]
     *
     * @return array
     */
    private function getCredentials()
    {
        $loggingConfig = config('logging');

        if (!isset($loggingConfig['cloudwatch'])) {
            throw new IncompleteCloudWatchConfig('Configuration Missing for Cloudwatch Log');
        }

        $cloudWatchConfigs = $loggingConfig['cloudwatch'];

        if (!isset($cloudWatchConfigs['key'], $cloudWatchConfigs['secret'], $cloudWatchConfigs['region'])) {
            throw new IncompleteCloudWatchConfig('Configuration Missing for Cloudwatch Log');
        }

        return $awsCredentials = [
            'region' => $cloudWatchConfigs['region'],
            'version' => $cloudWatchConfigs['version'],
            'credentials' => [
                'key' => $cloudWatchConfigs['key'],
                'secret' => $cloudWatchConfigs['secret'],
            ],
        ];
    }
}
