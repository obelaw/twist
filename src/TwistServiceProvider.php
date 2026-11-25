<?php

declare(strict_types=1);

namespace Obelaw\Twist;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use Obelaw\Twist\Addons\AddonsPool;
use Obelaw\Twist\Classes\TwistManager;
use Obelaw\Twist\Console\ActionStateCommand;
use Obelaw\Twist\Console\MakeCommand;
use Obelaw\Twist\Console\MigrateCommand;
use Obelaw\Twist\Console\SetupAddonCommand;
use Obelaw\Twist\Console\SetupClearCommand;
use Obelaw\Twist\Console\SetupCommand;
use Obelaw\Twist\Console\SetupDisableCommand;
use Obelaw\Twist\Console\SetupEnableCommand;

final class TwistServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/twist.php', 'twist');

        $this->app->singleton(TwistManager::class);
        $this->app->alias(TwistManager::class, 'obelaw.twist.twist-class');
    }

    public function boot(): void
    {
        AddonsPool::setPoolPath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'addons', AddonsPool::LEVELONE);

        $this->loadViewsFrom(__DIR__ . '/../resources', 'obelaw-twist');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            $this->publishes([
                __DIR__ . '/../config/twist.php' => config_path('twist.php'),
            ], 'twist-config');

            $this->commands([
                ActionStateCommand::class,
                SetupCommand::class,
                SetupAddonCommand::class,
                SetupEnableCommand::class,
                SetupDisableCommand::class,
                SetupClearCommand::class,
                MigrateCommand::class,
                MakeCommand::class,
            ]);
        }

        AboutCommand::add('Obelawium', static fn () => ['Obelaw Twist' => '1.0.0']);
    }
}
