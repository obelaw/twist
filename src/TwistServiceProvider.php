<?php

namespace Obelaw\Twist;

use Illuminate\Support\ServiceProvider;
use Obelaw\Twist\Classes\TwistClass;
use Obelaw\Twist\Console\MigrateCommand;
use Obelaw\Twist\Console\SetupAddonCommand;
use Obelaw\Twist\Console\SetupCommand;
use Obelaw\Twist\Console\SetupDisableCommand;

class TwistServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('obelaw.twist.twist-class', TwistClass::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources', 'obelaw-twist');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        $this->commands([
            SetupCommand::class,
            SetupAddonCommand::class,
            SetupDisableCommand::class,
            MigrateCommand::class,
        ]);
    }
}
