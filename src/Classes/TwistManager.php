<?php

declare(strict_types=1);

namespace Obelaw\Twist\Classes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Obelaw\Twist\Base\BaseAddon;
use Obelaw\Twist\Contracts\HasHooks;
use Obelaw\Twist\Contracts\HasRouteApi;
use Obelaw\Twist\Models\Addon;

class TwistManager
{
    private bool $disloadSetupAddons = false;

    /** @var list<BaseAddon> */
    private array $addons = [];

    /** @var list<string> */
    private array $availableAddons = [];

    /** @var list<string> */
    private array $migratePaths = [];

    private ?string $connection = null;

    private ?string $uploadDirectory = null;

    public function make(): static
    {
        return $this;
    }

    public function defaultPanel(): string
    {
        $panels = (array) config('twist.panels', ['obelaw']);

        return (string) ($panels[0] ?? 'obelaw');
    }

    /**
     * @return list<BaseAddon>
     */
    public function loadSetupAddons(?string $panel = null): array
    {
        if ($this->disloadSetupAddons) {
            return [];
        }

        $addonModel = new Addon();
        $tableAddons = $addonModel->getTable();
        $connName = $addonModel->getConnectionName();
        $schema = $connName ? Schema::connection($connName) : Schema::getFacadeRoot();
        $db = $connName ? DB::connection($connName) : DB::connection();

        $loadAddons = [];

        if ($schema->hasTable($tableAddons)) {
            $pointers = $db->table($tableAddons)
                ->where(function ($query) use ($panel): void {
                    if ($panel !== null && $panel !== '') {
                        $query->whereJsonContains('panels', $panel);
                    }
                })
                ->where('is_active', true)
                ->pluck('pointer')
                ->all();

            foreach ($pointers as $pointer) {
                if (! is_string($pointer) || ! class_exists($pointer)) {
                    continue;
                }

                $instance = $pointer::make();
                if ($instance instanceof BaseAddon && $instance->isEnabledForCurrentTenant()) {
                    $loadAddons[] = $instance;
                }
            }
        }

        $this->addons = array_values(array_merge($this->addons, $loadAddons));

        return $loadAddons;
    }

    /**
     * @return list<BaseAddon>
     */
    public function getAddons(): array
    {
        return $this->addons;
    }

    /**
     * @param list<BaseAddon> $addons
     */
    public function appendAddons(array $addons): self
    {
        foreach ($addons as $addon) {
            $this->appendAddon($addon);
        }

        return $this;
    }

    public function appendAddon(BaseAddon $addon): self
    {
        $this->addons[] = $addon;

        return $this;
    }

    public function resetAddons(): self
    {
        $this->addons = [];
        $this->availableAddons = [];

        return $this;
    }

    public function reset(): self
    {
        $this->disloadSetupAddons = false;
        $this->addons = [];
        $this->availableAddons = [];
        $this->migratePaths = [];
        $this->connection = null;
        $this->uploadDirectory = null;

        return $this;
    }

    public function addMigratePath(string $path): self
    {
        $this->migratePaths[] = $path;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getMigratePaths(): array
    {
        return $this->migratePaths;
    }

    public function hasAddon(string $id): bool
    {
        $addonModel = new Addon();
        $tableAddons = $addonModel->getTable();
        $connName = $addonModel->getConnectionName();
        $schema = $connName ? Schema::connection($connName) : Schema::getFacadeRoot();
        $db = $connName ? DB::connection($connName) : DB::connection();

        if ($schema->hasTable($tableAddons) && $this->availableAddons === []) {
            $this->availableAddons = $db->table($tableAddons)
                ->where('is_active', true)
                ->pluck('id')
                ->all();
        }

        return in_array($id, $this->availableAddons, true);
    }

    public function getDisloadSetupAddons(): bool
    {
        return $this->disloadSetupAddons;
    }

    public function disloadSetupAddons(): self
    {
        $this->disloadSetupAddons = true;

        return $this;
    }

    public function getUploadDirectory(): ?string
    {
        return $this->uploadDirectory;
    }

    public function getConnection(): ?string
    {
        return $this->connection;
    }

    public function setConnection(?string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function setUploadDirectory(?string $uploadDirectory): self
    {
        $this->uploadDirectory = $uploadDirectory;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getRoutesApi(): array
    {
        return $this->loadRoutesApiFromAddons($this->loadSetupAddons());
    }

    /**
     * @param list<BaseAddon> $addons
     * @return list<string>
     */
    public function loadRoutesApiFromAddons(array $addons): array
    {
        $apiRoutes = [];

        foreach ($addons as $addon) {
            if ($addon instanceof HasRouteApi) {
                $apiRoutes[] = $addon->pathRouteApi();
            }
        }

        return $apiRoutes;
    }

    public function loadHooks(): void
    {
        foreach ($this->loadSetupAddons() as $addon) {
            if ($addon instanceof HasHooks) {
                $addon->hooks();
            }
        }
    }
}
