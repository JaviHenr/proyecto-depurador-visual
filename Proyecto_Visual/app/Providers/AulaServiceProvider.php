<?php

namespace App\Providers;

use App\Models\Aula\{Actividad, Codigo, Seccion};
use App\Policies\Aula\{ActividadPolicy, CodigoPolicy, SeccionPolicy};
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AulaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Seccion::class, SeccionPolicy::class);
        Gate::policy(Actividad::class, ActividadPolicy::class);
        Gate::policy(Codigo::class, CodigoPolicy::class);
        // Conservar espacios y saltos de línea del código enviado por el editor.
        TrimStrings::except(['contenido_codigo']);
        Inertia::share('aulaFlash', fn () => request()->hasSession() ? request()->session()->get('aula_status') : null);
    }
}
