<?php

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users', // usa el broker por defecto 'users'
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users', // <-- debe existir abajo
        ],
        'api' => [
            'driver' => 'token',
            'provider' => 'users',
            'hash' => false,
        ],
    ],

    'providers' => [
        'users' => [ // <-- coincide con guards.web.provider
            'driver' => 'eloquent',
            'model'  => App\Models\Usuario::class, // tu modelo personalizado
        ],
        // Si usas provider por DB, déjalo comentado:
        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'usuario',
        // ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users', // <-- coincide con providers.users
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
