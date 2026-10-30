<?php

use App\Http\Controllers\Ajustes\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/ajustes', [UsuarioController::class, 'index'])->name('ajustes');
    Route::get('/ajustes/usuarios/nuevo', [UsuarioController::class, 'create'])->name('ajustes.usuarios.create');
    Route::post('/ajustes/usuarios', [UsuarioController::class, 'store'])->name('ajustes.usuarios.store');
    Route::get('/ajustes/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->whereNumber('usuario')->name('ajustes.usuarios.edit');
    Route::put('/ajustes/usuarios/{usuario}', [UsuarioController::class, 'update'])->whereNumber('usuario')->name('ajustes.usuarios.update');
});
