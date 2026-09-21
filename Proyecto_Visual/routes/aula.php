<?php

use App\Http\Controllers\Aula\{ActividadController, CodigoController, EstudianteController, SeccionController};
use Illuminate\Support\Facades\Route;

// Incluir una sola vez desde web.php: require __DIR__.'/aula.php';
Route::middleware('auth')->name('aula.')->group(function () {
    Route::get('/secciones', [SeccionController::class, 'index'])->name('secciones.index');
    Route::post('/secciones', [SeccionController::class, 'store'])->name('secciones.store');
    Route::get('/secciones/{seccion}', [SeccionController::class, 'show'])->whereNumber('seccion')->name('secciones.show');
    Route::put('/secciones/{seccion}', [SeccionController::class, 'update'])->whereNumber('seccion')->name('secciones.update');
    Route::delete('/secciones/{seccion}', [SeccionController::class, 'destroy'])->whereNumber('seccion')->name('secciones.destroy');

    Route::post('/secciones/{seccion}/estudiantes', [EstudianteController::class, 'store'])->whereNumber('seccion')->name('estudiantes.store');
    Route::delete('/secciones/{seccion}/estudiantes/{estudiante}', [EstudianteController::class, 'destroy'])->whereNumber(['seccion', 'estudiante'])->name('estudiantes.destroy');

    Route::post('/secciones/{seccion}/actividades', [ActividadController::class, 'store'])->whereNumber('seccion')->name('actividades.store');
    Route::put('/secciones/{seccion}/actividades/{actividad}', [ActividadController::class, 'update'])->whereNumber(['seccion', 'actividad'])->name('actividades.update');
    Route::delete('/secciones/{seccion}/actividades/{actividad}', [ActividadController::class, 'destroy'])->whereNumber(['seccion', 'actividad'])->name('actividades.destroy');
    Route::delete('/secciones/{seccion}/actividades/{actividad}/vinculo', [ActividadController::class, 'desvincular'])->whereNumber(['seccion', 'actividad'])->name('actividades.desvincular');

    Route::get('/codigos', [CodigoController::class, 'index'])->name('codigos.index');
    Route::get('/codigos/nuevo', [CodigoController::class, 'create'])->name('codigos.create');
    Route::post('/codigos', [CodigoController::class, 'store'])->name('codigos.store');
    Route::get('/codigos/{codigo}/editar', [CodigoController::class, 'edit'])->whereNumber('codigo')->name('codigos.edit');
    Route::put('/codigos/{codigo}', [CodigoController::class, 'update'])->whereNumber('codigo')->name('codigos.update');
    Route::delete('/codigos/{codigo}', [CodigoController::class, 'destroy'])->whereNumber('codigo')->name('codigos.destroy');
});
