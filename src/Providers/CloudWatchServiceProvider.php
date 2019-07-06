<?php

namespace Pagevamp\Providers;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Illuminate\Support\ServiceProvider;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Formatter\LineFormatter;
use Pagevamp\Exceptions\IncompleteCloudWatchConfig;
use Pagevamp\Logger;

class CloudWatchServiceProvider extends ServiceProvider
{
    protected $logginConfig;

    public function boot()
    {
        if (!env('DISABLE_CLOUDWATCH_LOG')) {
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
                    return (new Logger($this->app->make('config')->get('logging.channels')))->log($level, $message, $context);
                }

                if ($app['cloudwatch.logger'] instanceof Logger) {
                    $app['cloudwatch.logger']->log($level, $message, $context);
                }
            });
        }
    }


    /**
     * Code "inspired" from here
     * https://aws.amazon.com/blogs/developer/php-application-logging-with-amazon-cloudwatch-logs-and-monolog
     * Laravel installation mentioned here did not work but PHP with Monolog worked, hence this package.
     */
    public function register()
    {
        if (!env('DISABLE_CLOUDWATCH_LOG')) {
            $this->mergeConfigFrom(
                __DIR__ . '/../../config/logging.php',
                'logging.channels'
            );

            $this->app->singleton('cloudwatch.logger', function () {
                return $this->getLogger();
            });
        }
    }


    /**
     * * Resolve a Formatter instance from configurations
     * as default.
     *
     * @param array $configs
     *
     * @return mixed
     *
     * @throws IncompleteCloudWatchConfig
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
