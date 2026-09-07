<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sistema de Envío de Correos Predeterminado (Mailer)
    |--------------------------------------------------------------------------
    |
    | Esta opción controla el servicio de correo por defecto para enviar emails
    | a menos que se especifique otro explícitamente. Todos los servicios
    | adicionales se pueden configurar dentro del arreglo "mailers".
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Configuraciones de los Mailers
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar todos los servicios de correo utilizados por tu
    | aplicación junto a sus respectivos ajustes.
    |
    | Laravel soporta una variedad de controladores de transporte para el envío.
    |
    | Soportados: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |             "postmark", "resend", "log", "array",
    |             "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Dirección de Remitente Global ("From")
    |--------------------------------------------------------------------------
    |
    | Puedes desear que todos los correos enviados por la aplicación provengan de
    | la misma dirección. Aquí puedes especificar el nombre y dirección que se
    | usarán globalmente para todos los correos salientes.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],

];
