# Obelaw Twist `v1.0.0`

Modular **Filament v5** admin panel and addon engine for the Obelawium (`obelaw/ium` `^1.0`) ERP ecosystem.

---

## Requirements

- **PHP** `^8.2`
- **Laravel** `^11.0 | ^12.0 | ^13.0`
- **Filament** `^5.0`
- **Obelaw IUM Core** `obelaw/ium` `^1.0`
- *(Optional for Multi-Tenancy)* **Obelaw IUM Tenancy** `obelaw/ium-tenancy` `^1.0`

---

## Installation

```bash
composer require obelaw/twist
```

Publish configuration:

```bash
php artisan vendor:publish --tag=twist-config
```

---

## Configuring a Filament v5 Panel with Twist & Multi-Tenancy

```php
use Filament\Panel;
use Filament\PanelProvider;
use Obelaw\Twist\Support\TwistPanel;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('obelaw')
            ->path('admin');

        // Load active Twist addons for this panel
        TwistPanel::configure($panel);

        // Enable Database-per-Tenant isolation across HTTP & Livewire requests
        TwistPanel::tenancy($panel);

        return $panel;
    }
}
```

### Building a Twist Addon (`v1.0.0` Standard)

```php
use Filament\Panel;
use Obelaw\Twist\Base\BaseAddon;

final class AccountingAddon extends BaseAddon
{
    public function moduleKey(): ?string
    {
        return 'accounting';
    }

    public function register(Panel $panel): void
    {
        $this->discoverComponents($panel, __DIR__ . '/Filament', 'Obelaw\\Twist\\Accounting\\Filament');
    }
}
```

- Extend `Obelaw\Twist\Base\BaseResource` on all Filament Resources (`$isScopedToTenant = false` is configured automatically for Database-per-Tenant isolation; no `HasDBTenancy` trait needed).
- Do **not** override `$panel->colors()` inside an addon; panel branding belongs to the host application.

---

## Testing

```bash
php vendor/bin/pest
```

---

## License

MIT License — see [LICENSE](LICENSE).
