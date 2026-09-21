<?php
require __DIR__.'/aula.php';
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;


// Redirigir la raíz al login o al dashboard según autenticación
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

// Rutas para invitados (no autenticados)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// Rutas protegidas (autenticados)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});


Route::get('/Compilador', function () {
    return \Inertia\Inertia::render('Compilador');
})->middleware('auth');

Route::get('/actividades', function () {
    return \Inertia\Inertia::render('Actividades');
})->middleware('auth')->name('actividades');

//Route::get('/secciones', function () {
//    return \Inertia\Inertia::render('Secciones');
//})->middleware('auth');

Route::get('/depurador', function () {
       return \Inertia\Inertia::render('DepuradorVisual');
   })->middleware('auth')->name('depurador');