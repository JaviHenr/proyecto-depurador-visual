<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Controlador de Sesión Predeterminado
    |--------------------------------------------------------------------------
    |
    | Esta opción determina el controlador de sesión por defecto que se utiliza
    | para las solicitudes entrantes. Laravel soporta diversas opciones de
    | almacenamiento para persistir los datos de sesión.
    |
    | Soportados: "file", "cookie", "database", "memcached",
    |             "redis", "dynamodb", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Tiempo de Vida de la Sesión (Lifetime)
    |--------------------------------------------------------------------------
    |
    | Aquí puedes especificar el número de minutos que la sesión puede permanecer
    | inactiva antes de expirar. Si deseas que expire inmediatamente al cerrar el
    | navegador, puedes indicarlo mediante la opción expire_on_close.
    |
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Cifrado de Sesión
    |--------------------------------------------------------------------------
    |
    | Esta opción permite especificar si todos los datos de la sesión deben ser
    | cifrados antes de guardarse. El cifrado es manejado automáticamente por Laravel.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Ubicación de Archivos de Sesión
    |--------------------------------------------------------------------------
    |
    | Al usar el controlador de sesión "file", los archivos se almacenan en disco.
    | La ubicación predeterminada se define aquí.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Conexión de Base de Datos para Sesiones
    |--------------------------------------------------------------------------
    |
    | Al usar los controladores "database" o "redis", puedes especificar la
    | conexión que debe emplearse para gestionar dichas sesiones.
    |
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Tabla de Base de Datos para Sesiones
    |--------------------------------------------------------------------------
    |
    | Al usar el controlador "database", puedes especificar la tabla en la
    | que se guardarán las sesiones activas.
    |
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Almacén de Caché para Sesiones
    |--------------------------------------------------------------------------
    |
    | Al utilizar un backend de sesión basado en caché, aquí defines el almacén
    | que guardará los datos entre solicitudes.
    |
    | Afecta a: "dynamodb", "memcached", "redis"
    |
    */

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Lotería de Limpieza de Sesiones
    |--------------------------------------------------------------------------
    |
    | Algunos controladores de sesión deben limpiar periódicamente las sesiones
    | expiradas. Estas son las probabilidades de que ocurra en una solicitud dada.
    | Por defecto, las probabilidades son de 2 en 100.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Nombre de la Cookie de Sesión
    |--------------------------------------------------------------------------
    |
    | Aquí puedes cambiar el nombre de la cookie de sesión creada por el framework.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-session',
    ),

    /*
    |--------------------------------------------------------------------------
    | Ruta (Path) de la Cookie de Sesión
    |--------------------------------------------------------------------------
    |
    | Determina la ruta en la cual la cookie estará disponible (por defecto la raíz).
    |
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Dominio de la Cookie de Sesión
    |--------------------------------------------------------------------------
    |
    | Determina el dominio y subdominios en los que la cookie estará disponible.
    |
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Cookies Exclusivas por HTTPS (Secure)
    |--------------------------------------------------------------------------
    |
    | Al habilitar esta opción, la cookie de sesión solo se enviará de vuelta
    | al servidor si el navegador utiliza una conexión segura HTTPS.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE'),

    /*
    |--------------------------------------------------------------------------
    | Acceso Exclusivo HTTP (HttpOnly)
    |--------------------------------------------------------------------------
    |
    | Al habilitar esta opción, JavaScript no podrá acceder al valor de la cookie,
    | haciendo que solo sea accesible mediante el protocolo HTTP para mayor seguridad.
    |
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Cookies Same-Site
    |--------------------------------------------------------------------------
    |
    | Determina cómo se comportan las cookies en solicitudes de origen cruzado,
    | ayudando a mitigar ataques CSRF.
    |
    | Soportados: "lax", "strict", "none", null
    |
    */

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Cookies Particionadas (Partitioned)
    |--------------------------------------------------------------------------
    |
    | Asocia la cookie con el sitio de nivel superior en contextos de origen cruzado.
    |
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

    /*
    |--------------------------------------------------------------------------
    | Serialización de Sesión
    |--------------------------------------------------------------------------
    |
    | Controla la estrategia de serialización para los datos de sesión (por defecto JSON).
    |
    | Soportados: "json", "php"
    |
    */

    'serialization' => 'json',

];
