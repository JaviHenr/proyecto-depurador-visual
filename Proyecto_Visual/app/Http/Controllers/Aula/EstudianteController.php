<?php

namespace App\Http\Controllers\Aula;

use App\Models\Aula\{PerfilUsuario, Seccion};
use Illuminate\Database\QueryException;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{DB, Gate};
use Illuminate\Validation\ValidationException;

class EstudianteController extends AulaController
{
    public function store(Request $request, Seccion $seccion): RedirectResponse
    {
        Gate::authorize('update', $seccion);
        $datos = $request->validate([
            'email_estudiante' => ['required', 'email', 'max:200'],
        ]);

        DB::transaction(function () use ($seccion, $datos) {
            $actual = Seccion::query()->lockForUpdate()->findOrFail($seccion->getKey());
            Gate::authorize('update', $actual);
            $estudiante = PerfilUsuario::query()
                ->where('rol', 'estudiante')
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($datos['email_estudiante'])])
                ->lockForUpdate()->first();

            if (!$estudiante) {
                throw ValidationException::withMessages([
                    'email_estudiante' => 'No existe una cuenta de estudiante con ese correo.',
                ]);
            }

            try {
                $actual->estudiantes()->attach($estudiante->getKey(), [
                    'fecha_inscripcion' => now(),
                ]);
            } catch (QueryException $error) {
                if ((string) $error->getCode() === '23505') {
                    throw ValidationException::withMessages([
                        'email_estudiante' => 'Ese estudiante ya está inscrito en la sección.',
                    ]);
                }
                throw $error;
            }
        });

        return to_route('aula.secciones.show', $seccion)
            ->with('aula_status', 'Estudiante agregado a la sección.');
    }

    public function destroy(Seccion $seccion, PerfilUsuario $estudiante): RedirectResponse
    {
        Gate::authorize('update', $seccion);
        DB::transaction(function () use ($seccion, $estudiante) {
            $actual = Seccion::query()->lockForUpdate()->findOrFail($seccion->getKey());
            Gate::authorize('update', $actual);
            abort_unless($actual->estudiantes()
                ->wherePivot('id_usuario', $estudiante->getKey())->exists(), 404);
            $actual->estudiantes()->detach($estudiante->getKey());
        });

        return to_route('aula.secciones.show', $seccion)
            ->with('aula_status', 'Estudiante retirado de la sección. Sus códigos se conservaron.');
    }
}
