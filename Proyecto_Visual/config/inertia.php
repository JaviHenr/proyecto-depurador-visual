<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Renderizado del Lado del Servidor (Server Side Rendering - SSR)
    |--------------------------------------------------------------------------
    |
    | Estas opciones configuran si Inertia utiliza y cómo utiliza SSR para
    | pre-renderizar cada solicitud inicial a las páginas de la aplicación,
    | entregando HTML generado en el servidor al navegador del usuario.
    |
    | Ver: https://inertiajs.com/server-side-rendering
    |
    */

    'ssr' => [
        'enabled' => true,
        'url' => 'http://127.0.0.1:13714',
        // 'bundle' => base_path('bootstrap/ssr/ssr.mjs'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Páginas (Pages)
    |--------------------------------------------------------------------------
    |
    | Estas opciones configuran cómo Inertia descubre los componentes de página
    | en el sistema de archivos. Las rutas y extensiones se usan para localizar
    | componentes al renderizar respuestas y durante las aserciones de pruebas.
    |
    */

    'pages' => [

        'paths' => [
            resource_path('js/pages'),
        ],

        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Pruebas (Testing)
    |--------------------------------------------------------------------------
    |
    | Los valores descritos aquí se utilizan para localizar componentes de
    | Inertia en el sistema de archivos. Por ejemplo, al usar `assertInertia`,
    | la aserción intenta ubicar el archivo relativo a las rutas especificadas.
    |
    */

    'testing' => [

        'ensure_pages_exist' => true,

    ],

];
