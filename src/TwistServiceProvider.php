<?php

namespace Obelaw\Twist;

use Illuminate\Support\ServiceProvider;
use Obelaw\Twist\Classes\TwistClass;
use Obelaw\Twist\Console\MigrateCommand;
use Obelaw\Twist\Console\SetupAddonCommand;
use Obelaw\Twist\Console\SetupClearCommand;
use Obelaw\Twist\Console\SetupCommand;
use Obelaw\Twist\Console\SetupDisableCommand;
use Obelaw\Twist\Console\SetupEnableCommand;

class TwistServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/twist.php',
            'twist'
        );

        $this->app->singleton('obelaw.twist.twist-class', TwistClass::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        \Obelaw\Twist\Addons\AddonsPool::setPoolPath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'addons', \Obelaw\Twist\Addons\AddonsPool::LEVELONE);

        $this->loadViewsFrom(__DIR__ . '/../resources', 'obelaw-twist');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            $this->publishes([
                __DIR__ . '/../config/twist.php' => config_path('twist.php'),
            ], 'twist');
        }

        $this->commands([
            SetupCommand::class,
            SetupAddonCommand::class,
            SetupEnableCommand::class,
            SetupDisableCommand::class,
            SetupClearCommand::class,
            MigrateCommand::class,
        ]);
    }
}
