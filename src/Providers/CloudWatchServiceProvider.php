<?php

namespace Pagevamp\Providers;

use Illuminate\Support\ServiceProvider;
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
        $logger = new \Pagevamp\Logger($this->app);
        $loggingConfig = $this->app->make('config')->get('logging.channels.cloudwatch');
        return $logger($loggingConfig);
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

}
