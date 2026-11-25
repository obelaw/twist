<?php

declare(strict_types=1);

use Filament\Panel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Obelaw\Ium\Abstracts\Domain;
use Obelaw\Ium\Facades\Ium;
use Obelaw\Ium\Modules\Module;
use Obelaw\Ium\Modules\ModuleScope;
use Obelaw\Ium\Tenancy\Data\ProvisionTenantData;
use Obelaw\Ium\Tenancy\Http\Middleware\InitializeTenancy;
use Obelaw\Twist\Base\BaseAddon;
use Obelaw\Twist\Support\TwistPanel;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'twist_tenancy_test_' . uniqid();
    mkdir($this->tempDir, 0777, true);
    config()->set('ium-tenancy.sqlite_directory', $this->tempDir);

    $this->artisan('migrate', ['--database' => 'testing'])->assertSuccessful();
});

afterEach(function () {
    DB::purge();
    if (is_dir($this->tempDir)) {
        foreach (glob($this->tempDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDir);
    }
});

it('registers InitializeTenancy persistently on Filament v5 Panel and Livewire', function () {
    $panel = Panel::make()->id('erp')->path('erp');

    TwistPanel::tenancy($panel);

    expect($panel->getMiddleware())->toContain(InitializeTenancy::class);
    expect(Livewire::getPersistentMiddleware())->toContain(InitializeTenancy::class);
});

it('isolates consecutive HTTP and Livewire component requests across two tenants using real SQLite databases', function () {
    $tenantA = ium()->tenancy()->provisioner()->provision(new ProvisionTenantData(key: 'acme', name: 'Acme Corp'));
    $tenantB = ium()->tenancy()->provisioner()->provision(new ProvisionTenantData(key: 'globex', name: 'Globex Inc'));

    foreach ([[$tenantA, 'Acme Secret'], [$tenantB, 'Globex Secret']] as [$tenant, $secret]) {
        ium()->tenancy()->context()->runForTenant($tenant->toIdentity(), static function () use ($secret): void {
            $conn = ium()->tenancy()->context()->connectionName();
            Schema::connection($conn)->create('twist_notes', static function ($table): void {
                $table->id();
                $table->string('title');
            });
            DB::connection($conn)->table('twist_notes')->insert(['title' => $secret]);
        });
    }

    $panel = Panel::make()->id('erp')->path('erp');
    TwistPanel::tenancy($panel);

    Livewire::component('twist-tenant-probe', TenantProbeComponent::class);

    Route::middleware([InitializeTenancy::class])->get('/twist-probe', static function () {
        $conn = ium()->tenancy()->context()->connectionName();
        $titles = DB::connection($conn)->table('twist_notes')->pluck('title')->all();

        return response()->json([
            'tenant' => ium()->tenancy()->context()->current()?->key,
            'titles' => $titles,
        ]);
    });

    // Request 1: Tenant A sees ONLY Acme Secret
    $this->withHeaders(['X-Tenant' => 'acme'])
        ->getJson('http://localhost/twist-probe')
        ->assertOk()
        ->assertJson(['tenant' => 'acme', 'titles' => ['Acme Secret']]);

    $this->flushHeaders();

    // Request 2: Tenant B sees ONLY Globex Secret (zero leakage from Request 1!)
    $this->withHeaders(['X-Tenant' => 'globex'])
        ->getJson('http://localhost/twist-probe')
        ->assertOk()
        ->assertJson(['tenant' => 'globex', 'titles' => ['Globex Secret']]);

    // Livewire component execution inside Tenant A vs Tenant B
    ium()->tenancy()->context()->runForTenant($tenantA->toIdentity(), function (): void {
        Livewire::test(TenantProbeComponent::class)
            ->assertSet('tenantKey', 'acme')
            ->assertSet('noteTitle', 'Acme Secret')
            ->call('addNote', 'Acme Livewire Update')
            ->assertSet('noteCount', 2);
    });

    ium()->tenancy()->context()->runForTenant($tenantB->toIdentity(), function (): void {
        Livewire::test(TenantProbeComponent::class)
            ->assertSet('tenantKey', 'globex')
            ->assertSet('noteTitle', 'Globex Secret')
            ->assertSet('noteCount', 1);
    });
});

it('gates BaseAddon::isEnabledForCurrentTenant() according to per-tenant module activation', function () {
    Ium::registerModule(Module::make(
        name: 'accounting',
        domain: StubAccountingDomain::class,
        scope: ModuleScope::Tenant,
    ));

    $tenantA = ium()->tenancy()->provisioner()->provision(new ProvisionTenantData(key: 'alpha', name: 'Alpha'));
    $tenantB = ium()->tenancy()->provisioner()->provision(new ProvisionTenantData(key: 'beta', name: 'Beta'));

    ium()->tenancy()->modules()->enable($tenantA, 'accounting');
    ium()->tenancy()->modules()->disable($tenantB, 'accounting');

    $addon = new AccountingGatedAddon();

    $enabledForA = ium()->tenancy()->context()->runForTenant($tenantA->toIdentity(), static fn (): bool => $addon->isEnabledForCurrentTenant());
    $enabledForB = ium()->tenancy()->context()->runForTenant($tenantB->toIdentity(), static fn (): bool => $addon->isEnabledForCurrentTenant());

    expect($enabledForA)->toBeTrue()
        ->and($enabledForB)->toBeFalse();
});

final class TenantProbeComponent extends Component
{
    public ?string $tenantKey = null;

    public ?string $noteTitle = null;

    public int $noteCount = 0;

    public function mount(): void
    {
        $this->refreshState();
    }

    public function addNote(string $title): void
    {
        $conn = ium()->tenancy()->context()->connectionName();
        DB::connection($conn)->table('twist_notes')->insert(['title' => $title]);
        $this->refreshState();
    }

    private function refreshState(): void
    {
        $this->tenantKey = ium()->tenancy()->context()->current()?->key;
        $conn = ium()->tenancy()->context()->connectionName();
        $rows = DB::connection($conn)->table('twist_notes')->orderBy('id')->pluck('title')->all();
        $this->noteTitle = $rows[0] ?? null;
        $this->noteCount = count($rows);
    }

    public function render(): string
    {
        return '<div>{{ $tenantKey }}: {{ $noteTitle }} ({{ $noteCount }})</div>';
    }
}

final class StubAccountingDomain extends Domain {}

final class AccountingGatedAddon extends BaseAddon
{
    public function moduleKey(): ?string
    {
        return 'accounting';
    }

    public function register(Panel $panel): void {}
}
