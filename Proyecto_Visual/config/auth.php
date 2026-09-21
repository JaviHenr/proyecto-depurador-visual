<?php

use App\Models\Usuario;

return [

    /*
    |--------------------------------------------------------------------------
    | Valores Predeterminados de Autenticación
    |--------------------------------------------------------------------------
    |
    | Esta opción define el "guard" (guardián) de autenticación y el "broker"
    | de restablecimiento de contraseña por defecto para la aplicación. Puedes
    | cambiar estos valores según sea necesario.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guardianes de Autenticación (Guards)
    |--------------------------------------------------------------------------
    |
    | A continuación puedes definir cada uno de los guardianes de autenticación.
    | Se incluye una configuración predeterminada que utiliza almacenamiento
    | en sesión junto con el proveedor de usuarios Eloquent.
    |
    | Todos los guards tienen un proveedor de usuarios, que define cómo se
    | recuperan los usuarios de la base de datos u otro almacenamiento.
    |
    | Soportados: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Proveedores de Usuarios (User Providers)
    |--------------------------------------------------------------------------
    |
    | Todos los guards de autenticación tienen un proveedor de usuarios, que
    | define cómo se obtienen los usuarios desde la base de datos o almacenamiento.
    | Habitualmente se utiliza Eloquent.
    |
    | Si tienes múltiples tablas o modelos de usuarios, puedes configurar múltiples
    | proveedores y asignarlos a diferentes guards de autenticación.
    |
    | Soportados: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', Usuario::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Restablecimiento de Contraseñas
    |--------------------------------------------------------------------------
    |
    | Estas opciones especifican el comportamiento del restablecimiento de
    | contraseñas en Laravel, incluyendo la tabla para almacenar tokens y el
    | proveedor de usuarios que se invoca para recuperarlos.
    |
    | El tiempo de expiración (expire) son los minutos durante los cuales el
    | token será válido. El límite de frecuencia (throttle) define los segundos
    | que un usuario debe esperar antes de generar nuevos tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tiempo de Espera para Confirmación de Contraseña
    |--------------------------------------------------------------------------
    |
    | Aquí puedes definir el número de segundos antes de que expire la ventana
    | de confirmación de contraseña y se pida al usuario que vuelva a ingresarla.
    | Por defecto, el tiempo de espera dura 3 horas (10800 segundos).
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
