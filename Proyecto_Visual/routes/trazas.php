<?php

use App\Http\Controllers\Aula\TrazaArchivoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->name('depurador.json.')->group(function () {
    Route::get('/depurador/trazas/archivos', [TrazaArchivoController::class, 'index'])->name('archivos');
    Route::get('/depurador/trazas/archivo', [TrazaArchivoController::class, 'show'])->name('archivo');
});
