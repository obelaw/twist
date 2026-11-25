<?php

declare(strict_types=1);

namespace Obelaw\Twist\Tests;

use Filament\FilamentServiceProvider;
use Filament\Support\SupportServiceProvider;
use Livewire\LivewireServiceProvider;
use Obelaw\Ium\Providers\ObelawiumServiceProvider;
use Obelaw\Ium\Tenancy\Providers\IumTenancyServiceProvider;
use Obelaw\Twist\Addons\AddonRegistrar;
use Obelaw\Twist\Addons\AddonsPool;
use Obelaw\Twist\Facades\Twist;
use Obelaw\Twist\TwistServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            FilamentServiceProvider::class,
            ObelawiumServiceProvider::class,
            IumTenancyServiceProvider::class,
            TwistServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:6Cu/eaCLkLzW19Y7W1+kZ+RzF1/5tN2qE8O7r5m4r9s=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('ium-tenancy.central_connection', 'testing');
        $app['config']->set('ium-tenancy.template_connection', 'testing');
        $app['config']->set('cache.default', 'array');
    }

    protected function tearDown(): void
    {
        Twist::reset();
        AddonRegistrar::reset();
        AddonsPool::reset();

        parent::tearDown();
    }
}
