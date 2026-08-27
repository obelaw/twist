<?php

namespace Obelaw\Twist\Classes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Obelaw\Twist\Base\BaseAddon;
use Obelaw\Twist\Contracts\HasHooks;
use Obelaw\Twist\Contracts\HasRouteApi;
use Obelaw\Twist\Models\Addon;

class TwistClass
{
    private bool $disloadSetupAddons = false;
    private array $addons = [];
    private array $availableAddons = [];
    private array $migratePaths = [];
    private string|null $uploadDirectory  = null;

    public function make(): static
    {
        return $this;
    }

    public function loadSetupAddons(string|null $panel = null): array
    {
        $tableAddons = (new Addon)->getTable();

        $loadAddons = [];

        if (Schema::hasTable($tableAddons)) {
            $addons = array_map(function ($pointer) {
                if (class_exists($pointer))
                    return (new $pointer)->make();
            }, DB::table($tableAddons)->where(function ($query) use ($panel) {
                if ($panel)
                    $query->whereJsonContains('panels', $panel);
            })->where('is_active', true)->pluck('pointer')->toArray());

            $filteredAddons = array_filter($addons, function ($value) {
                return $value !== null;
            });

            $loadAddons = array_merge($loadAddons, $filteredAddons);
        }

        $this->addons = array_merge($this->addons, $loadAddons);

        return $loadAddons;
    }

    /**
     * Get the value of addons
     */
    public function getAddons()
    {
        return $this->addons;
    }

    /**
     * Set the value of modules
     *
     * @return  self
     */
    public function appendAddons(array $addons)
    {
        foreach ($addons as $addon) {
            $this->appendAddon($addon);
        }

        return $this;
    }

    /**
     * Set the value of addon
     *
     * @return  self
     */
    public function appendAddon(BaseAddon $addon)
    {
        array_push($this->addons, $addon);

        return $this;
    }

    /**
     * Set the value of addons
     *
     * @return  self
     */
    public function resetAddons()
    {
        $this->addons = [];

        return $this;
    }

    /**
     * Add a migration path.
     *
     * @return  self
     */
    public function addMigratePath(string $path)
    {
        array_push($this->migratePaths, $path);

        return $this;
    }

    /**
     * Get all migration paths.
     */
    public function getMigratePaths(): array
    {
        return $this->migratePaths;
    }

    /**
     * Check if the addon is available
     * 
     * @param mixed $id
     * @return bool
     */
    public function hasAddon($id): bool
    {
        $tableAddons = (new Addon)->getTable();

        if (Schema::hasTable($tableAddons) && empty($this->availableAddons)) {
            $this->availableAddons = DB::table($tableAddons)->where('is_active', true)->pluck('id')->toArray();
        }

        return in_array($id, $this->availableAddons);
    }

    /**
     * Get the value of disloadSetupAddons
     */
    public function getDisloadSetupAddons()
    {
        return $this->disloadSetupAddons;
    }

    /**
     * Set the value of disloadSetupAddons
     *
     * @return  self
     */
    public function disloadSetupAddons()
    {
        $this->disloadSetupAddons = true;

        return $this;
    }

    /**
     * Get the value of uploadDirectory
     */
    public function getUploadDirectory()
    {
        return $this->uploadDirectory;
    }

    /**
     * Set the value of uploadDirectory
     *
     * @return  self
     */
    public function setUploadDirectory($uploadDirectory)
    {
        $this->uploadDirectory = $uploadDirectory;

        return $this;
    }

    public function getRoutesApi(): array
    {
        $addons = $this->loadSetupAddons();

        $apiRoutes = $this->loadRoutesApiFromAddons($addons);

        return $apiRoutes;
    }

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
        $addons = $this->loadSetupAddons();

        foreach ($addons as $addon) {
            if ($addon instanceof HasHooks) {
                $addon->hooks();
            }
        }
    }
}
