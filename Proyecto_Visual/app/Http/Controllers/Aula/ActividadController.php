<?php

namespace App\Http\Controllers\Aula;

use App\Models\Aula\{Actividad, Seccion};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{DB, Gate};
use Illuminate\Validation\ValidationException;

class ActividadController extends AulaController
{
    public function store(Request $request, Seccion $seccion): RedirectResponse
    {
        Gate::authorize('create', [Actividad::class, $seccion]);
        $datos = $this->datos($request);
        DB::transaction(function () use ($seccion, $datos) {
            $actual = Seccion::query()->lockForUpdate()->findOrFail($seccion->getKey());
            Gate::authorize('create', [Actividad::class, $actual]);
            $actividad = new Actividad($datos);
            $actividad->fecha_creacion_actividad = now();
            $actividad->save();
            // Crear otra actividad añade otro vínculo, sin reemplazar los existentes.
            $actual->actividades()->attach($actividad->getKey());
        });
        return to_route('aula.secciones.show', $seccion)->with('aula_status', 'Actividad creada en la sección.');
    }

    public function update(Request $request, Seccion $seccion, Actividad $actividad): RedirectResponse
    {
        Gate::authorize('update', $seccion);
        $datos = $this->datos($request);
        DB::transaction(function () use ($seccion, $actividad, $datos) {
            [, $actual] = $this->vinculoBloqueado($seccion, $actividad);
            Gate::authorize('update', $actual);
            $actual->update($datos);
        });
        return to_route('aula.secciones.show', $seccion)->with('aula_status', 'Actividad actualizada.');
    }

    public function destroy(Seccion $seccion, Actividad $actividad): RedirectResponse
    {
        Gate::authorize('update', $seccion);
        DB::transaction(function () use ($seccion, $actividad) {
            [$seccionActual, $actividadActual] = $this->vinculoBloqueado($seccion, $actividad);
            Gate::authorize('delete', $actividadActual);
            if ($actividadActual->codigos()->exists()) {
                throw ValidationException::withMessages(['eliminar' => 'La actividad tiene códigos asociados. Sus autores deben desvincularlos o eliminarlos primero.']);
            }
            $seccionActual->actividades()->detach($actividadActual->getKey());
            $this->eliminarRegistro($actividadActual, 'La actividad está siendo utilizada por otros registros.');
        });
        return to_route('aula.secciones.show', $seccion)->with('aula_status', 'Actividad eliminada. Las demás actividades se conservan.');
    }

    public function desvincular(Seccion $seccion, Actividad $actividad): RedirectResponse
    {
        Gate::authorize('update', $seccion);
        DB::transaction(function () use ($seccion, $actividad) {
            [$seccionActual, $actividadActual] = $this->vinculoBloqueado($seccion, $actividad);
            if ($actividadActual->secciones()->count() < 2) {
                throw ValidationException::withMessages(['eliminar' => 'La actividad ya no está compartida. Actualiza la página y usa Eliminar actividad.']);
            }
            $seccionActual->actividades()->detach($actividadActual->getKey());
        });
        return to_route('aula.secciones.show', $seccion)->with('aula_status', 'Vínculo retirado. La actividad continúa en sus otras secciones.');
    }

    private function vinculoBloqueado(Seccion $seccion, Actividad $actividad): array
    {
        // Mismo orden en todos los cambios de vínculos existentes: actividad, luego sección.
        $actividadActual = Actividad::query()->lockForUpdate()->findOrFail($actividad->getKey());
        $seccionActual = Seccion::query()->lockForUpdate()->findOrFail($seccion->getKey());
        Gate::authorize('update', $seccionActual);
        abort_unless($seccionActual->actividades()->where('actividad.id_actividad', $actividadActual->getKey())->exists(), 404);
        return [$seccionActual, $actividadActual];
    }

    private function datos(Request $request): array
    {
        return $request->validate([
            'nombre_actividad' => ['required', 'string', 'max:150'],
            'descripcion_actividad' => ['nullable', 'string', 'max:200'],
            'instrucciones' => ['nullable', 'string', 'max:200'],
        ]);
    }
}
