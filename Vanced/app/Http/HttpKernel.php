<?php

namespace App\Http;

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\SubstituteBindings;

return [
    'middlewareGroups' => [
        'api' => [
            EnsureFrontendRequestsAreStateful::class, // 👈 ESTE ES EL CRÍTICO
            ThrottleRequests::class . ':api',
            SubstituteBindings::class,
        ],
    ],
];
