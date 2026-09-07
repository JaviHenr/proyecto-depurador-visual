<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Almacén de Caché Predeterminado
    |--------------------------------------------------------------------------
    |
    | Esta opción controla el almacén de caché por defecto utilizado por el
    | framework. Esta conexión se utiliza si no se especifica otra explícitamente
    | al ejecutar una operación de caché en la aplicación.
    |
    */

    'default' => env('CACHE_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Almacenes de Caché (Cache Stores)
    |--------------------------------------------------------------------------
    |
    | Aquí puedes definir todos los "almacenes" de caché de tu aplicación así
    | como sus controladores. Puedes definir múltiples almacenes para el mismo
    | controlador para agrupar diferentes tipos de elementos almacenados.
    |
    | Controladores soportados: "array", "database", "file", "memcached",
    |                           "redis", "dynamodb", "storage", "octane",
    |                           "session", "failover", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'storage' => [
            'driver' => 'storage',
            'disk' => env('CACHE_STORAGE_DISK'),
            'path' => env('CACHE_STORAGE_PATH', 'framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

        'failover' => [
            'driver' => 'failover',
            'stores' => [
                'database',
                'array',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Prefijo de Claves de Caché
    |--------------------------------------------------------------------------
    |
    | Al utilizar almacenes como APC, base de datos, memcached, Redis o DynamoDB,
    | puede haber otras aplicaciones compartiendo la misma caché. Por esa razón,
    | puedes añadir un prefijo a cada clave para evitar colisiones.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-cache-'),

    /*
    |--------------------------------------------------------------------------
    | Clases Deserializables
    |--------------------------------------------------------------------------
    |
    | Este valor determina las clases que pueden deserializarse desde el almacén
    | de caché. Por defecto, ninguna clase de PHP se deserializará para prevenir
    | ataques de cadena de gadgets (gadget chain attacks) en caso de fuga de APP_KEY.
    |
    */

    'serializable_classes' => false,

];
