<?php

declare(strict_types=1);

namespace Obelaw\Twist\Support;

use Filament\Panel;
use InvalidArgumentException;
use Livewire\Livewire;
use Obelaw\Twist\Facades\Twist;

/**
 * Fluent configurator for Filament v5 Panels powered by Obelaw Twist.
 *
 * Bridges Filament v5 panels and Livewire requests with `obelaw/ium-tenancy`
 * in Database-per-Tenant mode without Filament single-DB `tenant_id` scoping.
 */
final class TwistPanel
{
    /**
     * Configure a Filament v5 panel with active Twist addons registered for that panel.
     */
    public static function configure(Panel $panel): Panel
    {
        $addons = Twist::loadSetupAddons($panel->getId());

        if ($addons !== []) {
            $panel->plugins($addons);
        }

        return $panel;
    }

    /**
     * Enable Database-per-Tenant isolation on a Filament v5 panel.
     *
     * Registers the tenant initialization middleware (defaulting to
     * `Obelaw\Ium\Tenancy\Http\Middleware\InitializeTenancy`) as persistent
     * middleware on BOTH the Filament Panel and Livewire so every initial page
     * load and subsequent Livewire XHR update runs inside the resolved tenant's
     * isolated database, cache, filesystem, and session context.
     *
     * @param class-string|null $middleware Optional custom tenancy middleware class.
     * @param list<class-string> $extraMiddleware Additional persistent middleware classes.
     */
    public static function tenancy(
        Panel $panel,
        ?string $middleware = null,
        array $extraMiddleware = [],
    ): Panel {
        $resolvedMiddleware = $middleware
            ?? (string) config('twist.tenancy.middleware', 'Obelaw\\Ium\\Tenancy\\Http\\Middleware\\InitializeTenancy');

        if ($resolvedMiddleware === '' || ! class_exists($resolvedMiddleware)) {
            throw new InvalidArgumentException(
                "Tenancy middleware [{$resolvedMiddleware}] does not exist. Install `obelaw/ium-tenancy` or pass a valid middleware class."
            );
        }

        $persistentStack = array_values(array_unique(array_merge([$resolvedMiddleware], $extraMiddleware)));

        // Register persistently on the Filament v5 Panel (covers panel routes + Livewire)
        $panel->middleware($persistentStack, isPersistent: true);

        // Also register directly with Livewire so non-panel Livewire component updates remain tenant-isolated
        if (class_exists(Livewire::class)) {
            Livewire::addPersistentMiddleware($persistentStack);
        }

        return $panel;
    }
}
