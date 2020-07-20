<?php

namespace Pagevamp;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Formatter\LineFormatter;
use Pagevamp\Exceptions\IncompleteCloudWatchConfig;

class Logger
{

    private $app;

    public function __construct($app = null)
    {
        $this->app = $app;
    }

    public function __invoke(array $config)
    {
        if($this->app === null) {
            $this->app = \app();
        }

        $loggingConfig = $config;
        $cwClient = new CloudWatchLogsClient($this->getCredentials());

        $streamName = $loggingConfig['stream_name'];
        $retentionDays = $loggingConfig['retention'];
        $groupName = $loggingConfig['group_name'];
        $batchSize = isset($loggingConfig['batch_size']) ? $loggingConfig['batch_size'] : 10000;

        $logHandler = new CloudWatch($cwClient, $groupName, $streamName, $retentionDays, $batchSize);
        $logger = new \Monolog\Logger($loggingConfig['name']);

        $formatter = $this->resolveFormatter($loggingConfig);
        $logHandler->setFormatter($formatter);
        $logger->pushHandler($logHandler);

        return $logger;
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
