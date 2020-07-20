<?php

namespace Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\LogglyFormatter;
use Monolog\Logger;
use Pagevamp\Exceptions\IncompleteCloudWatchConfig;
use PHPUnit\Framework\TestCase;
use \Mockery;

class LoggerTest extends TestCase
{

    public function testGetLoggerShouldResolveCustomFormatterInstanceFromConfiguration()
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
            'formatter' => JsonFormatter::class,
        ];

        $config = Mockery::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('logging.channels')
            ->andReturn([
                'cloudwatch' => $cloudwatchConfigs,
            ]);
        $config->shouldReceive('get')
            ->once()
            ->with('logging.channels.cloudwatch')
            ->andReturn($cloudwatchConfigs);

        $formatter = Mockery::mock(JsonFormatter::class);

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->once()
            ->with('config')
            ->andReturn($config);
        $app->shouldReceive('make')
            ->once()
            ->with(JsonFormatter::class)
            ->andReturn($formatter);

        $provider = new \Pagevamp\Logger($app);
        $logger = $provider($cloudwatchConfigs);

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertNotEmpty($logger->getHandlers());
        $this->assertInstanceOf(
            JsonFormatter::class,
            $logger->getHandlers()[0]->getFormatter()
        );
    }

    public function testGetLoggerShouldResolveDefaultFormatterInstanceWhenConfigIsNull()
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
            'formatter' => null,
        ];

        $config = Mockery::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('logging.channels')
            ->andReturn([
                'cloudwatch' => $cloudwatchConfigs,
            ]);
        $config->shouldReceive('get')
            ->once()
            ->with('logging.channels.cloudwatch')
            ->andReturn($cloudwatchConfigs);

        $formatter = Mockery::mock(JsonFormatter::class);

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->once()
            ->with('config')
            ->andReturn($config);
        $app->shouldReceive('make')
            ->once()
            ->with(JsonFormatter::class)
            ->andReturn($formatter);

        $provider = new \Pagevamp\Logger($app);
        $logger = $provider($cloudwatchConfigs);

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertNotEmpty($logger->getHandlers());
        $this->assertInstanceOf(
            LineFormatter::class,
            $logger->getHandlers()[0]->getFormatter()
        );
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
        ];

        $config = Mockery::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('logging.channels')
            ->andReturn([
                'cloudwatch' => $cloudwatchConfigs,
            ]);
        $config->shouldReceive('get')
            ->once()
            ->with('logging.channels.cloudwatch')
            ->andReturn($cloudwatchConfigs);

        $formatter = Mockery::mock(LineFormatter::class);

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->once()
            ->with('config')
            ->andReturn($config);
        $app->shouldReceive('make')
            ->once()
            ->with(LineFormatter::class)
            ->andReturn($formatter);

        $provider = new \Pagevamp\Logger($app);
        $logger = $provider($cloudwatchConfigs);

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertNotEmpty($logger->getHandlers());
        $this->assertInstanceOf(
            LineFormatter::class,
            $logger->getHandlers()[0]->getFormatter()
        );
    }

    public function testGetLoggerShouldResolveCallableFormatter()
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
            'formatter' => function ($configs) {
                return new LogglyFormatter();
            },
        ];

        $config = Mockery::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('logging.channels')
            ->andReturn([
                'cloudwatch' => $cloudwatchConfigs,
            ]);
        $config->shouldReceive('get')
            ->once()
            ->with('logging.channels.cloudwatch')
            ->andReturn($cloudwatchConfigs);

        $formatter = Mockery::mock(LogglyFormatter::class);

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->once()
            ->with('config')
            ->andReturn($config);
        $app->shouldReceive('make')
            ->once()
            ->with(LogglyFormatter::class)
            ->andReturn($formatter);

        $provider = new \Pagevamp\Logger($app);
        $logger = $provider($cloudwatchConfigs);

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertNotEmpty($logger->getHandlers());
        $this->assertInstanceOf(
            LogglyFormatter::class,
            $logger->getHandlers()[0]->getFormatter()
        );
    }

    public function testInvalidFormatterWillThrowException()
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

        $config = Mockery::mock(Repository::class);
        $config->shouldReceive('get')
            ->once()
            ->with('logging.channels')
            ->andReturn([
                'cloudwatch' => $cloudwatchConfigs,
            ]);
        $config->shouldReceive('get')
            ->once()
            ->with('logging.channels.cloudwatch')
            ->andReturn($cloudwatchConfigs);

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->once()
            ->with('config')
            ->andReturn($config);

        $this->expectException(IncompleteCloudWatchConfig::class);
        $provider = new \Pagevamp\Logger($app);
        $provider($cloudwatchConfigs);
    }
}
