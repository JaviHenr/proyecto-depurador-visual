<?php

namespace App\Http\Controllers\Aula;

use App\Models\Aula\Seccion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{DB, Gate};
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class SeccionController extends AulaController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Seccion::class);
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $filtros['q'] ?? '';
        $secciones = Seccion::query()->visiblesPara($request->user())
            ->withCount(['actividades', 'estudiantes'])
            ->when($q !== '', fn (Builder $query) => $query->whereRaw('LOWER(nombre_seccion) LIKE ?', ['%'.mb_strtolower($q).'%']))
            ->orderByDesc('id_seccion')->paginate(12)->withQueryString();

        return $this->pagina('Aula/Secciones/Index', [
            'secciones' => $secciones, 'filtros' => ['q' => $q],
            'puedeCrear' => Gate::allows('create', Seccion::class),
        ]);
    }

    public function show(Request $request, Seccion $seccion): Response
    {
        Gate::authorize('view', $seccion);
        $seccion->load('profesor')->loadCount(['actividades', 'estudiantes']);
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $filtros['q'] ?? '';
        $actividades = $seccion->actividades()->withCount(['secciones', 'codigos'])
            ->when($q !== '', fn ($query) => $query->whereRaw('LOWER(nombre_actividad) LIKE ?', ['%'.mb_strtolower($q).'%']))
            ->orderByDesc('actividad.id_actividad')->paginate(10)->withQueryString();
        // Acceso a esta sección ya autorizado. Solo el profesor responsable administra.
        $actividades->through(fn ($actividad) => $actividad->only([
            'id_actividad', 'nombre_actividad', 'descripcion_actividad', 'instrucciones',
            'fecha_creacion_actividad', 'secciones_count', 'codigos_count',
        ]) + ['permisos' => [
            'editar' => Gate::allows('update', $actividad),
            'desvincular' => Gate::allows('update', $seccion) && $actividad->secciones_count > 1,
        ]]);

        $puedeGestionar = Gate::allows('update', $seccion);
        $estudiantes = null;
        if ($puedeGestionar) {
            $estudiantes = $seccion->estudiantes()
                ->orderBy('nombre_usuario')
                ->paginate(10, [
                    'usuario.id_usuario', 'usuario.nombre_usuario', 'usuario.email',
                ], 'estudiantes_page')->withQueryString();
            $estudiantes->through(fn ($estudiante) => [
                'id_usuario' => (int) $estudiante->getKey(),
                'nombre_usuario' => $estudiante->nombre_usuario,
                'email' => $estudiante->email,
                'fecha_inscripcion' => $estudiante->pivot?->fecha_inscripcion,
            ]);
        }

        return $this->pagina('Aula/Secciones/Show', [
            'seccion' => $seccion->only([
                'id_seccion', 'id_usuario', 'nombre_seccion', 'descripcion_seccion',
                'actividades_count', 'estudiantes_count',
            ]),
            'profesor' => $seccion->profesor?->nombre_usuario ?? $seccion->profesor?->name ?? 'Profesor',
            'actividades' => $actividades,
            'estudiantes' => $estudiantes,
            'filtros' => ['q' => $q],
            'permisos' => ['gestionar' => $puedeGestionar],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Seccion::class);
        $seccion = new Seccion($this->datos($request));
        $seccion->id_usuario = $request->user()->getAuthIdentifier();
        $seccion->save();
        return to_route('aula.secciones.show', $seccion)->with('aula_status', 'Sección creada.');
    }

    public function update(Request $request, Seccion $seccion): RedirectResponse
    {
        Gate::authorize('update', $seccion);
        $seccion->update($this->datos($request));
        return to_route('aula.secciones.show', $seccion)->with('aula_status', 'Sección actualizada.');
    }

    public function destroy(Seccion $seccion): RedirectResponse
    {
        Gate::authorize('delete', $seccion);
        DB::transaction(function () use ($seccion) {
            $actual = Seccion::query()->lockForUpdate()->findOrFail($seccion->getKey());
            Gate::authorize('delete', $actual);
            if ($actual->actividades()->exists()) {
                throw ValidationException::withMessages(['eliminar' => 'La sección todavía tiene actividades. Elimínalas o retira sus vínculos si están compartidas antes de eliminar la sección.']);
            }
            // Las matrículas se eliminan por ON DELETE CASCADE. No se elimina ningún usuario.
            $this->eliminarRegistro($actual, 'No se puede eliminar: existen registros que usan esta sección.');
        });
        return to_route('aula.secciones.index')->with('aula_status', 'Sección eliminada.');
    }

    private function datos(Request $request): array
    {
        return $request->validate([
            'nombre_seccion' => ['required', 'string', 'max:200'],
            'descripcion_seccion' => ['nullable', 'string', 'max:300'],
        ]);
    }
}
