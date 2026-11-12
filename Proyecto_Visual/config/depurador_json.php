<?php

use App\Http\TrazaArchivo;

return [
    // Archivos adicionales de práctica dentro del proyecto Laravel.
    'directorio' => storage_path('app/trazas'),
    'archivo_inicial' => TrazaArchivo::PRACTICA,
    'max_bytes' => 5 * 1024 * 1024,
    'max_archivos' => 200,
];
