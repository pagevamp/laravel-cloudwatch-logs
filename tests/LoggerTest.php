<?php

namespace Tests;

use Monolog\Formatter\LineFormatter;
use Pagevamp\Exceptions\IncompleteCloudWatchConfig;
use Pagevamp\Logger;
use PHPUnit\Framework\TestCase;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LogglyFormatter;

class LoggerTest extends TestCase
{
    public function testExceptionIsThrownWhenIncorrectCredentialsIsGiven()
    {
        $cloudwatchConfigs = [
            'name' => '',
            'region' => '',
            'credentials' => [
                'key' => '',
                'secret' => '',
            ],
            'stream_name' => 'laravel_app',
            'retention' => 14,
            'group_name' => 'laravel_app',
            'version' => 'latest',
            'formatter' => 'InvalidFormatter',
        ];


        $this->expectException(IncompleteCloudWatchConfig::class);
        (new Logger($cloudwatchConfigs));
    }

    public function testGetLoggerShouldResolveDefaultFormatterInstanceWhenConfigIsNotSetted()
    {
        $cloudwatchConfigs = [
            'name' => '',
            'region' => '',
            'credentials' => [
                'key' => '',
                'secret' => '',
            ],
            'stream_name' => 'laravel_app',
            'retention' => 14,
            'group_name' => 'laravel_app',
            'version' => 'latest',
            'cloudwatch' => [
                'name' => '',
                'region' => '',
                'credentials' => [
                    'key' => '',
                    'secret' => '',
                ],
                'stream_name' => 'laravel_app',
                'retention' => 14,
                'group_name' => 'laravel_app',
                'version' => 'latest',
            ]
        ];


        $logger = (new Logger($cloudwatchConfigs));

//        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertNotEmpty($logger->getHandlers());
        $this->assertInstanceOf(
            LineFormatter::class,
            $logger->getHandlers()[0]->getFormatter()
        );
    }

}

