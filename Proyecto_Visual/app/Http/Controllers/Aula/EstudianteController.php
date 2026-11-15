<?php

namespace App\Http\Controllers\Aula;

use App\Models\Aula\PerfilUsuario;
use App\Models\Aula\Seccion;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EstudianteController extends AulaController
{
    public function store(Request $request, Seccion $seccion): RedirectResponse
    {
        Gate::authorize('update', $seccion);
        $datos = $request->validate([
            'id_estudiante' => ['required_without:email_estudiante', 'prohibits:email_estudiante', 'integer', 'min:1'],
            'email_estudiante' => ['required_without:id_estudiante', 'email', 'max:200'],
        ]);

        DB::transaction(function () use ($seccion, $datos) {
            $actual = Seccion::query()->lockForUpdate()->findOrFail($seccion->getKey());
            Gate::authorize('update', $actual);
            $porId = isset($datos['id_estudiante']);
            $campo = $porId ? 'id_estudiante' : 'email_estudiante';
            $consulta = PerfilUsuario::query()->where('rol', 'estudiante');
            if ($porId) {
                $consulta->whereKey($datos['id_estudiante']);
            } else {
                $consulta->whereRaw('LOWER(email) = ?', [mb_strtolower($datos['email_estudiante'])]);
            }
            $estudiante = $consulta->lockForUpdate()->first();

            if (! $estudiante) {
                throw ValidationException::withMessages([
                    $campo => 'No se encontró la cuenta del estudiante seleccionado.',
                ]);
            }

            if ($actual->estudiantes()->wherePivot('id_usuario', $estudiante->getKey())->exists()) {
                throw ValidationException::withMessages([
                    $campo => 'Ese estudiante ya está inscrito en la sección.',
                ]);
            }

            try {
                $actual->estudiantes()->attach($estudiante->getKey(), [
                    'fecha_inscripcion' => now(),
                ]);
            } catch (QueryException $error) {
                if ((string) $error->getCode() === '23505') {
                    throw ValidationException::withMessages([
                        $campo => 'Ese estudiante ya está inscrito en la sección.',
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
