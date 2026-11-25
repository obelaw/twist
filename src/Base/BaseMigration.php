<?php

declare(strict_types=1);

namespace Obelaw\Twist\Base;

use Illuminate\Database\Migrations\Migration;

abstract class BaseMigration extends Migration
{
    protected string $prefix = '';

    protected string $postfix = '';

    public function __construct()
    {
        $this->prefix = rtrim((string) config('obelaw.database.table_prefix', 'obelaw_'), '_') . '_' . $this->postfix;
    }
}
