<?php

return [
    // Fuente provisional: archivos JSON dentro del proyecto Laravel.
    'directorio' => storage_path('app/trazas'),
    'archivo_inicial' => 'traza_contador_suma.json',
    'max_bytes' => 5 * 1024 * 1024,
    'max_archivos' => 200,
];
