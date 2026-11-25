<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Registered Twist Panels
    |--------------------------------------------------------------------------
    */
    'panels' => ['obelaw'],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Integration (Database-per-Tenant)
    |--------------------------------------------------------------------------
    |
    | Default middleware used by TwistPanel::tenancy() when obelaw/ium-tenancy
    | is installed. Applied persistently to both HTTP and Livewire requests.
    |
    */
    'tenancy' => [
        'middleware' => \Obelaw\Ium\Tenancy\Http\Middleware\InitializeTenancy::class,
    ],
];
