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
        $this->publishes([
            __DIR__.'/../config/logging.php' => config_path('logging.php')
        ], 'config');
    }
}
