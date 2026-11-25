<?php

declare(strict_types=1);

namespace Obelaw\Twist\Base;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Container\Container;
use Obelaw\Ium\Contracts\TenantContext;
use Obelaw\Ium\Facades\Ium;

/**
 * Standardized base Filament v5 Plugin for all Obelaw Twist UI addons.
 *
 * Conventions enforced in Twist v1.0:
 * - Addons MUST NOT override host application panel colors (`$panel->colors()`).
 * - Addons backed by an `obelaw/ium` module declare `moduleKey()` so Twist
 *   can honor per-tenant module activation automatically.
 */
abstract class BaseAddon implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return class_basename($this);
    }

    /**
     * Optional backing `obelaw/ium` module slug (e.g. 'accounting', 'inventory').
     * Used for per-tenant module activation checks when multi-tenancy is active.
     */
    public function moduleKey(): ?string
    {
        return null;
    }

    /**
     * Determine whether this addon is active for the currently resolved tenant.
     *
     * In single-tenant mode (or when no `moduleKey()` is declared), returns true.
     * In multi-tenant mode, delegates to `TenantModuleManager` if bound in the container.
     */
    public function isEnabledForCurrentTenant(): bool
    {
        $moduleKey = $this->moduleKey();
        if ($moduleKey === null || $moduleKey === '') {
            return true;
        }

        $container = Container::getInstance();

        if (! $container->bound(TenantContext::class)) {
            return true;
        }

        /** @var TenantContext $context */
        $context = $container->make(TenantContext::class);
        $tenant = $context->current();

        if ($tenant === null) {
            return true;
        }

        $managerClass = 'Obelaw\\Ium\\Tenancy\\Services\\TenantModuleManager';
        $tenantModelClass = 'Obelaw\\Ium\\Tenancy\\Models\\Tenant';

        if (class_exists($managerClass) && class_exists($tenantModelClass) && $container->bound($managerClass)) {
            $tenantModel = $tenantModelClass::find($tenant->id);
            if ($tenantModel !== null) {
                return (bool) $container->make($managerClass)->isEnabled($tenantModel, $moduleKey);
            }
        }

        return Ium::modules()->has($moduleKey);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Register Resources, Pages, and Widgets from standard addon directories
     * without hijacking host panel branding/colors.
     */
    protected function discoverComponents(
        Panel $panel,
        string $baseDir,
        string $baseNamespace,
    ): void {
        $resourcesDir = $baseDir . DIRECTORY_SEPARATOR . 'Resources';
        if (is_dir($resourcesDir)) {
            $panel->discoverResources(in: $resourcesDir, for: "{$baseNamespace}\\Resources");
        }

        $pagesDir = $baseDir . DIRECTORY_SEPARATOR . 'Pages';
        if (is_dir($pagesDir)) {
            $panel->discoverPages(in: $pagesDir, for: "{$baseNamespace}\\Pages");
        }

        $widgetsDir = $baseDir . DIRECTORY_SEPARATOR . 'Widgets';
        if (is_dir($widgetsDir)) {
            $panel->discoverWidgets(in: $widgetsDir, for: "{$baseNamespace}\\Widgets");
        }
    }
}
