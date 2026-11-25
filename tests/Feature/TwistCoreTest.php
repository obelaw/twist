<?php

declare(strict_types=1);

use Filament\Panel;
use Obelaw\Twist\Addons\AddonRegistrar;
use Obelaw\Twist\Base\BaseAddon;
use Obelaw\Twist\Base\BaseResource;
use Obelaw\Twist\Facades\Twist;
use Obelaw\Twist\Models\Addon;
use Obelaw\Twist\Support\TwistPanel;

it('boots TwistServiceProvider cleanly and resolves defaultPanel() for AddonRegistrar', function () {
    expect(Twist::defaultPanel())->toBe('obelaw');

    AddonRegistrar::register('demo', DummyTwistAddon::class);

    $paths = AddonRegistrar::getPaths();
    expect($paths)->toHaveKey('demo')
        ->and($paths['demo']['panels'])->toBe(['obelaw']);
});

it('removes legacy HasDBTenancy and configures BaseResource with isScopedToTenant = false', function () {
    expect(trait_exists('Obelaw\\Twist\\Tenancy\\Concerns\\HasDBTenancy'))->toBeFalse();
    expect(DummyTwistResource::isScopedToTenant())->toBeFalse();
});

it('loads active addons via TwistPanel::configure() without overriding panel colors', function () {
    $this->artisan('migrate', ['--database' => 'testing'])->assertSuccessful();

    Addon::create([
        'id' => 'dummy',
        'pointer' => DummyTwistAddon::class,
        'panels' => ['obelaw'],
        'is_active' => true,
    ]);

    $panel = Panel::make()->id('obelaw')->path('admin');
    TwistPanel::configure($panel);

    expect($panel->hasPlugin('DummyTwistAddon'))->toBeTrue();
});

final class DummyTwistAddon extends BaseAddon
{
    public function register(Panel $panel): void
    {
        // Clean addon: registers resources/pages only, never hijacks $panel->colors().
    }
}

final class DummyTwistResource extends BaseResource
{
    protected static ?string $model = Addon::class;
}
