<?php

declare(strict_types=1);

namespace Obelaw\Twist\Base;

use Filament\Resources\Resource;

/**
 * Standardized Filament v5 Resource for Obelaw Twist addons.
 *
 * Disables Filament's single-database `tenant_id` model scoping (`$isScopedToTenant = false`)
 * because Obelawium v1.0 enforces Database-per-Tenant isolation at the connection layer.
 * Replaces the obsolete `HasDBTenancy` trait.
 */
abstract class BaseResource extends Resource
{
    protected static bool $isScopedToTenant = false;
}
