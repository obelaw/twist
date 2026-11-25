<?php

declare(strict_types=1);

namespace Obelaw\Twist\Base;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Obelaw\Ium\Contracts\TenantContext;
use Obelaw\Twist\Facades\Twist;

abstract class BaseModel extends Model
{
    /**
     * Table postfix appended after the base table prefix.
     */
    protected string $postfix = '';

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $prefix = rtrim((string) config('obelaw.database.table_prefix', 'obelaw_'), '_') . '_' . $this->postfix;
        $table = $this->getTable();

        if (! str_starts_with($table, $prefix)) {
            $this->setTable($prefix . $table);
        }
    }

    /**
     * Dynamically resolve the database connection from:
     * 1. Explicit `$this->connection` set on the model.
     * 2. Explicit `Twist::getConnection()` override.
     * 3. Active `TenantContext::connectionName()` when running in multi-tenant mode.
     * 4. Laravel's default connection.
     */
    public function getConnectionName(): ?string
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $twistConnection = Twist::getConnection();
        if (is_string($twistConnection) && $twistConnection !== '') {
            return $twistConnection;
        }

        $container = Container::getInstance();
        if ($container->bound(TenantContext::class)) {
            /** @var TenantContext $context */
            $context = $container->make(TenantContext::class);
            $tenantConnection = $context->connectionName();
            if ($tenantConnection !== null && $tenantConnection !== '') {
                return $tenantConnection;
            }
        }

        return parent::getConnectionName();
    }
}
