# Changelog

All notable changes to `obelaw/twist` will be documented in this file.

## v1.2.0 - 2026-11-26

### Added
- Added `Obelaw\Twist\Support\TwistPanel::configure()` and `TwistPanel::tenancy()` bridging Filament v5 panels and Livewire requests with `obelaw/ium-tenancy` persistent middleware (`InitializeTenancy`).
- Added `Obelaw\Twist\Base\BaseResource` with `protected static bool $isScopedToTenant = false` for Database-per-Tenant isolation.
- Added `BaseAddon::moduleKey()`, `BaseAddon::isEnabledForCurrentTenant()`, and `BaseAddon::discoverComponents()` helpers (standardizing addons without hijacking host panel colors).
- Added `TwistManager::defaultPanel()` and `TwistManager::reset()` (fixing `AddonRegistrar::register()` when `$panels` is omitted).
- Added `AddonRegistrar::reset()` and `AddonsPool::reset()` for test/Octane isolation.
- Added Pest feature test suite verifying Filament v5 panel setup, Livewire persistent middleware isolation across two concurrent SQLite tenant databases, and per-tenant addon gating.
- Added `LICENSE`, `.gitattributes`, `phpunit.xml`, `README.md`, and PHP 8.2–8.4 / Laravel 11–13 / Filament 5 CI matrix.

### Changed
- Targeted **Filament `^5.0` ONLY** (`php ^8.2`, `laravel/framework ^11.0|^12.0|^13.0`, `obelaw/ium ^1.0`, `minimum-stability: stable`).
- Updated `BaseModel::getConnectionName()` to resolve active `TenantContext::connectionName()` dynamically and added double-prefix table guards in `BaseModel` and `BaseMigration`.

### Removed
- Removed obsolete `HasDBTenancy` trait, `TenancyManager`, `Tenancy` facade, `DriverFactory`, `MultiTenantDriver`, `IsolationDriver`, `TenantDTO`, `MigrateTenancyService`, `TenancyMigrateCommand`, and `config/tenancy.php` in favor of `obelaw/ium-tenancy` `^1.0`.
