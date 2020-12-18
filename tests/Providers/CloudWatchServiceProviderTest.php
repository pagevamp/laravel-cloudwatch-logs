<?php

namespace Tests\Providers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Mockery;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\LogglyFormatter;
use Monolog\Logger;
use Pagevamp\Exceptions\IncompleteCloudWatchConfig;
use Pagevamp\Providers\CloudWatchServiceProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\CallableStreamNameGenerator;

class CloudWatchServiceProviderTest extends TestCase
{
    public function testGetLoggerShouldResolveCustomFormatterInstanceFromConfiguration()
    {
        $cloudwatchConfigs = [
            'name' => '',
            'region' => 'eu-west-2',
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

        $provider = new CloudWatchServiceProvider($app);
        $logger = $provider->getLogger();

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
            'region' => 'eu-west-2',
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

        $provider = new CloudWatchServiceProvider($app);
        $logger = $provider->getLogger();

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
            'region' => 'eu-west-2',
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

        $provider = new CloudWatchServiceProvider($app);
        $logger = $provider->getLogger();

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
            'region' => 'eu-west-2',
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

        $provider = new CloudWatchServiceProvider($app);
        $logger = $provider->getLogger();

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
            'region' => 'eu-west-2',
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
        $provider = new CloudWatchServiceProvider($app);
        $provider->getLogger();
    }

    public function testGetLoggerShouldResolveCallableStreamName()
    {
        $cloudwatchConfigs = [
            'name' => '',
            'region' => 'eu-west-2',
            'credentials' => [
                'key' => '',
                'secret' => '',
            ],
            'stream_name' => [CallableStreamNameGenerator::class, 'generateStreamName'],
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

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')
            ->once()
            ->with('config')
            ->andReturn($config);

        $provider = new CloudWatchServiceProvider($app);
        $logger = $provider->getLogger();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertNotEmpty($logger->getHandlers());

        // stream_name will end up as the result of CallableStreamNameGenerator::generateStreamName, however I cannot
        // find a getter for that value to assert with.
    }
}
