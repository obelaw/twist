<?php

namespace Obelaw\Twist\Classes;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Obelaw\Twist\Base\BaseAddon;
use Obelaw\Twist\Contracts\HasDispatcher;
use Obelaw\Twist\Contracts\HasRouteApi;
use Obelaw\Twist\Models\Addon;
use Pharaonic\Laravel\Executor\Facades\ExecutorPool;

class TwistClass
{
    private $panel = null;
    private string|null $domain = null;
    private string $path = 'obelaw';
    private string $color = '#FC4706';
    private mixed $logo = null;
    private string|null $connection = null;
    private string $prefixTable = 'obelaw_';
    private array $middlewares = [
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
        SubstituteBindings::class,
    ];
    private bool $disloadSetupAddons = false;
    private array $addons = [];
    private array $availableAddons = [];
    private string|null $uploadDirectory  = null;

    public function make(): static
    {
        return $this;
    }

    /**
     * Get the value of path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the value of path
     *
     * @return  self
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the value of color
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set the value of color
     *
     * @return  self
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get the value of logo
     */
    public function getLogo()
    {
        return $this->logo ?? fn() => view('obelaw-twist::layout.logo');
    }

    /**
     * Set the value of logo
     *
     * @return  self
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get the value of middlewares
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * Set the value of middlewares
     *
     * @return  self
     */
    public function setMiddleware($middleware): static
    {
        array_push($this->middlewares, $middleware);

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
     * Get the value of panel
     */
    public function getPanel()
    {
        return $this->panel;
    }

    public function defaultPanel()
    {
        return 'obelaw';
    }

    /**
     * Get the value of domain
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set the value of domain
     *
     * @return  self
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Set the value of panel
     *
     * @return  self
     */
    public function setPanel($panel)
    {
        $this->panel = $panel;

        return $this;
    }

    /**
     * Get the value of connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the value of connection
     *
     * @return  self
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the value of prefixTable
     */
    public function getPrefixTable()
    {
        return $this->prefixTable;
    }

    /**
     * Set the value of prefixTable
     *
     * @return  self
     */
    public function setPrefixTable($prefixTable)
    {
        $this->prefixTable = $prefixTable;

        return $this;
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

    public function loadDispatchers(): array
    {
        $addons = $this->loadSetupAddons();

        $dispatchers = $this->loadDispatchersFromAddons($addons);

        array_map(function ($dispatcher) {
            ExecutorPool::addPath($dispatcher);
        }, $dispatchers);

        return $dispatchers;
    }

    public function loadDispatchersFromAddons(array $addons): array
    {
        $dispatchers = [];

        foreach ($addons as $addon) {
            if ($addon instanceof HasDispatcher) {
                $dispatchers[] = $addon->pathDispatchers();
            }
        }

        return $dispatchers;
    }
}
