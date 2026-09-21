<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nombre de la Aplicación
    |--------------------------------------------------------------------------
    |
    | Este valor es el nombre de tu aplicación, el cual se utilizará cuando el
    | framework necesite colocar el nombre en una notificación u otros
    | elementos de la interfaz donde deba mostrarse el nombre del sistema.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Entorno de la Aplicación
    |--------------------------------------------------------------------------
    |
    | Este valor determina el "entorno" en el que se está ejecutando la
    | aplicación actualmente. Esto puede influir en cómo se configuran diversos
    | servicios que utiliza la aplicación. Configúralo en tu archivo ".env".
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Modo de Depuración de la Aplicación
    |--------------------------------------------------------------------------
    |
    | Cuando la aplicación está en modo de depuración (debug), se mostrarán
    | mensajes detallados de error con trazas de pila (stack traces) ante cada error.
    | Si está desactivado, se mostrará una página de error genérica y simple.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | URL de la Aplicación
    |--------------------------------------------------------------------------
    |
    | Esta URL es utilizada por la consola para generar URLs adecuadamente al
    | utilizar la herramienta de línea de comandos Artisan. Debes configurarla con
    | la raíz de la aplicación para que esté disponible en los comandos de Artisan.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Zona Horaria de la Aplicación
    |--------------------------------------------------------------------------
    |
    | Aquí puedes especificar la zona horaria predeterminada para tu aplicación,
    | la cual será utilizada por las funciones de fecha y hora de PHP. Por
    | defecto está configurada en "UTC", adecuada para la mayoría de los casos.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Configuración Regional (Locale) de la Aplicación
    |--------------------------------------------------------------------------
    |
    | La configuración regional determina el idioma predeterminado utilizado por
    | los métodos de traducción y localización de Laravel. Esta opción puede
    | configurarse para cualquier idioma para el que tengas cadenas de texto.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Clave de Cifrado
    |--------------------------------------------------------------------------
    |
    | Esta clave es utilizada por los servicios de cifrado de Laravel y debe
    | establecerse en una cadena aleatoria de 32 caracteres para garantizar
    | la seguridad de los valores cifrados. Hazlo antes de desplegar la aplicación.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', '')),
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Controlador del Modo de Mantenimiento
    |--------------------------------------------------------------------------
    |
    | Estas opciones determinan el controlador utilizado para gestionar el estado
    | de "modo mantenimiento" de Laravel. El controlador "cache" permite que el
    | modo de mantenimiento sea controlado a través de múltiples servidores.
    |
    | Controladores soportados: "file", "cache", "array"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
