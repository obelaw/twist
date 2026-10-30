<?php

namespace Obelaw\Twist\Services\Tenancy;

use Obelaw\Twist\Base\BaseService;
use Illuminate\Support\Facades\DB;
use Obelaw\Twist\Contracts\HasMigration;
use Obelaw\Twist\Facades\Tenancy;
use Obelaw\Twist\Facades\Twist;
use Obelaw\Twist\Tenancy\DTO\TenantDTO;

class MigrateTenancyService extends BaseService
{
    public function migrateAddons($tenant)
    {
        $defaultConn = config('database.default', 'mysql');
        $base = config("database.connections.{$defaultConn}") ?? config('database.connections.mysql');

        $driver = $base['driver'] ?? 'mysql';
        if ($driver === 'sqlite') {
            $dbName = database_path('tenant_' . $tenant->id . '.sqlite');
            if (!file_exists($dbName)) {
                touch($dbName);
            }
        } else {
            $dbName = ($base['database'] ?? 'laravel') . '_' . $tenant->id;
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
        }

        $migratePaths = Twist::getMigratePaths();

        foreach (Twist::getAddons() as $addon) {
            if ($addon instanceof HasMigration) {
                array_push($migratePaths, $addon->pathMigrations());
            }
        }

        $tenantDTO = new TenantDTO(database: $dbName, id: $tenant->id);
        Tenancy::migrate($tenantDTO, $migratePaths);
    }
}
