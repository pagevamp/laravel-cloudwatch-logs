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
        $isCloudWatchDisabled = $this->app->make('config')->get('logging.channels.cloudwatch.disabled');
        if (!$isCloudWatchDisabled) {
            $app = $this->app;
            $app['log']->listen(function () use ($app) {
                $args = \func_get_args();

                // Laravel 5.4 returns a MessageLogged instance only
                if (1 == \count($args)) {
                    $level = $args[0]->level;
                    $message = $args[0]->message;
                    $context = $args[0]->context;
                } else {
                    $level = $args[0];
                    $message = $args[1];
                    $context = $args[2];
                }

                if ($message instanceof \ErrorException) {
                    return $this->getLogger()->log($level, $message, $context);
                }

                if ($app['cloudwatch.logger'] instanceof Logger) {
                    $app['cloudwatch.logger']->log($level, $message, $context);
                }
            });
        }
    }

    public function getLogger()
    {
        $cwClient = new CloudWatchLogsClient($this->getCredentials());
        $loggingConfig = $this->app->make('config')->get('logging.channels.cloudwatch');

        $streamName = $this->resolveStreamName($loggingConfig['stream_name']);
        $retentionDays = $loggingConfig['retention'];
        $groupName = $loggingConfig['group_name'];
        $batchSize = isset($loggingConfig['batch_size']) ? $loggingConfig['batch_size'] : 10000;

        $logHandler = new CloudWatch($cwClient, $groupName, $streamName, $retentionDays, $batchSize);
        $logger = new Logger($loggingConfig['name']);

        $formatter = $this->resolveFormatter($loggingConfig);
        $logHandler->setFormatter($formatter);
        $logger->pushHandler($logHandler);

        return $logger;
    }

    protected function resolveStreamName($configuredValue)
    {
        if (is_callable($configuredValue)) {
            return call_user_func($configuredValue);
        }

        return $configuredValue;
    }

    /**
     * Code "inspired" from here
     * https://aws.amazon.com/blogs/developer/php-application-logging-with-amazon-cloudwatch-logs-and-monolog
     * Laravel installation mentioned here did not work but PHP with Monolog worked, hence this package.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/logging.php',
            'logging.channels'
        );

        if (!$this->app->make('config')->get('logging.channels.cloudwatch.disabled')) {
            $this->app->singleton('cloudwatch.logger', function () {
                return $this->getLogger();
            });
        }
    }

    /**
     * This is the way config should be defined in config/logging.php
     * in key cloudwatch.
     *
     * 'cloudwatch' => [
     *     'name' => env('CLOUDWATCH_LOG_NAME', ''),
     *     'region' => env('CLOUDWATCH_LOG_REGION', ''),
     *     'credentials' => [
     *         'key' => env('CLOUDWATCH_LOG_KEY', ''),
     *         'secret' => env('CLOUDWATCH_LOG_SECRET', '')
     *     ],
     *     'stream_name' => env('CLOUDWATCH_LOG_STREAM_NAME', 'laravel_app'),
     *     'retention' => env('CLOUDWATCH_LOG_RETENTION_DAYS', 14),
     *     'group_name' => env('CLOUDWATCH_LOG_GROUP_NAME', 'laravel_app'),
     *     'version' => env('CLOUDWATCH_LOG_VERSION', 'latest'),
     * ]
     *
     * @return array
     *
     * @throws \Pagevamp\Exceptions\IncompleteCloudWatchConfig
     */
    protected function getCredentials()
    {
        $loggingConfig = $this->app->make('config')->get('logging.channels');

        if (!isset($loggingConfig['cloudwatch'])) {
            throw new IncompleteCloudWatchConfig('Configuration Missing for Cloudwatch Log');
        }

        $cloudWatchConfigs = $loggingConfig['cloudwatch'];

        if (!isset($cloudWatchConfigs['region'])) {
            throw new IncompleteCloudWatchConfig('Missing region key-value');
        }

        $awsCredentials = [
            'region' => $cloudWatchConfigs['region'],
            'version' => $cloudWatchConfigs['version'],
        ];

        if ($cloudWatchConfigs['credentials']['key']) {
            $awsCredentials['credentials'] = $cloudWatchConfigs['credentials'];
        }

        return $awsCredentials;
    }

    /**
     * @return mixed|LineFormatter
     *
     * @throws IncompleteCloudWatchConfig
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function resolveFormatter(array $configs)
    {
        if (!isset($configs['formatter'])) {
            return new LineFormatter(
                '%channel%: %level_name%: %message% %context% %extra%',
                null,
                false,
                true
            );
        }

        $formatter = $configs['formatter'];

        if (\is_string($formatter) && class_exists($formatter)) {
            return $this->app->make($formatter);
        }

        if (\is_callable($formatter)) {
            return $formatter($configs);
        }

        throw new IncompleteCloudWatchConfig('Formatter is missing for the logs');
    }
}
