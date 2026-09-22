<?php

namespace App\Http\Controllers\Aula;

use App\Models\Aula\{Actividad, Codigo};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{DB, Gate};
use Illuminate\Support\Arr;
use Illuminate\Validation\{Rule, ValidationException};
use Inertia\Response;

class CodigoController extends AulaController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Codigo::class);
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'actividad' => ['nullable', 'integer', 'min:1'],
            'vista' => ['nullable', Rule::in(['mios', 'estudiantes'])],
        ]);
        $q = $filtros['q'] ?? '';
        $actividadId = isset($filtros['actividad']) ? (int) $filtros['actividad'] : null;
        $puedeVerEstudiantes = $request->user()->rol === 'profesor';
        $vista = $puedeVerEstudiantes ? ($filtros['vista'] ?? 'mios') : 'mios';

        $consulta = Codigo::query()
            ->with([
                'autor' => fn ($autor) => $autor->select(['id_usuario', 'nombre_usuario', 'email']),
                'actividad' => fn ($actividad) => $actividad->select(['id_actividad', 'nombre_actividad']),
            ]);
        if ($vista === 'estudiantes') {
            $consulta->deEstudiantesVisiblesPara($request->user());
        } else {
            $consulta->where('codigo.id_usuario', $request->user()->getAuthIdentifier());
        }

        $codigos = $consulta
            ->select(['id_codigo', 'id_actividad', 'id_usuario', 'nombre_codigo', 'nombre_archivo', 'formato', 'fecha_creacion_codigo'])
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $busqueda) => $busqueda
                ->whereRaw('LOWER(nombre_codigo) LIKE ?', ['%'.mb_strtolower($q).'%'])
                ->orWhereRaw('LOWER(nombre_archivo) LIKE ?', ['%'.mb_strtolower($q).'%'])
                ->when($vista === 'estudiantes', fn (Builder $porAutor) => $porAutor
                    ->orWhereHas('autor', fn (Builder $autor) => $autor
                        ->whereRaw('LOWER(nombre_usuario) LIKE ?', ['%'.mb_strtolower($q).'%'])
                        ->orWhereRaw('LOWER(email) LIKE ?', ['%'.mb_strtolower($q).'%'])))))
            ->when($actividadId !== null, fn (Builder $query) => $query->where('id_actividad', $actividadId))
            ->orderByDesc('id_codigo')->paginate(15)->withQueryString();
        $codigos->through(fn (Codigo $codigo) => $codigo->only([
            'id_codigo', 'id_actividad', 'id_usuario', 'nombre_codigo', 'nombre_archivo',
            'formato', 'fecha_creacion_codigo',
        ]) + [
            'autor_nombre' => $codigo->autor?->nombre_usuario ?? 'Usuario',
            'autor_email' => $codigo->autor?->email,
            'actividad_nombre' => $codigo->actividad?->nombre_actividad,
            'permisos' => ['editar' => Gate::allows('update', $codigo)],
        ]);

        return $this->pagina('Aula/Codigos/Index', [
            'codigos' => $codigos,
            'filtros' => ['q' => $q, 'actividad' => $actividadId, 'vista' => $vista],
            'vista' => $vista,
            'puedeVerEstudiantes' => $puedeVerEstudiantes,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Codigo::class);
        $datos = $request->validate(['actividad' => ['nullable', 'integer', Rule::exists('actividad', 'id_actividad')]]);
        $actividadId = isset($datos['actividad']) ? (int) $datos['actividad'] : null;
        $this->autorizarAsociacion($actividadId);
        return $this->editor($request, null, $actividadId);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Codigo::class);
        $datos = $this->datos($request);
        $codigo = DB::transaction(function () use ($request, $datos) {
            $actividadId = isset($datos['id_actividad']) ? (int) $datos['id_actividad'] : null;
            $this->autorizarAsociacion($actividadId, bloquear: true);
            $codigo = new Codigo(Arr::except($datos, ['id_actividad']));
            $codigo->id_usuario = $request->user()->getAuthIdentifier();
            $codigo->id_actividad = $actividadId;
            $codigo->fecha_creacion_codigo = now()->toDateString();
            $codigo->save();
            return $codigo;
        });
        return to_route('depurador', ['codigo' => $codigo])
            ->with('aula_status', 'Código guardado. Ya puedes editarlo y depurarlo.');
    }

    public function edit(Codigo $codigo): RedirectResponse
    {
        Gate::authorize('view', $codigo);
        return to_route('depurador', ['codigo' => $codigo]);
    }

    public function depurador(Request $request, ?Codigo $codigo = null): Response
    {
        Gate::authorize('viewAny', Codigo::class);
        if ($codigo) {
            Gate::authorize('view', $codigo);
            $codigo->load(['autor', 'actividad']);
        }

        return $this->pagina('DepuradorVisual', [
            'codigo' => $codigo ? $codigo->only([
                'id_codigo', 'id_actividad', 'id_usuario', 'nombre_codigo', 'nombre_archivo',
                'formato', 'contenido_codigo', 'fecha_creacion_codigo',
            ]) + ['huella' => $codigo->huella()] : null,
            'autorCodigo' => $codigo ? [
                'nombre' => $codigo->autor?->nombre_usuario ?? 'Usuario',
                'email' => $codigo->autor?->email,
            ] : null,
            'actividadCodigo' => $codigo?->actividad?->nombre_actividad,
            'permisosCodigo' => [
                'editar' => $codigo ? Gate::allows('update', $codigo) : false,
            ],
        ]);
    }

    public function update(Request $request, Codigo $codigo): RedirectResponse
    {
        Gate::authorize('update', $codigo);
        $datos = $this->datos($request);
        $request->validate(['huella' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/']]);
        $actividadId = isset($datos['id_actividad']) ? (int) $datos['id_actividad'] : null;
        DB::transaction(function () use ($request, $codigo, $datos, $actividadId) {
            // Orden de bloqueos compartido con ActividadController: actividad, luego código.
            $destino = $actividadId === null ? null
                : Actividad::query()->lockForUpdate()->findOrFail($actividadId);
            $actual = Codigo::query()->lockForUpdate()->findOrFail($codigo->getKey());
            Gate::authorize('update', $actual);
            if (!hash_equals($actual->huella(), (string) $request->input('huella'))) {
                throw ValidationException::withMessages(['huella' => 'Este código cambió en otra pestaña. Copia tus cambios y vuelve a abrirlo antes de guardar.']);
            }
            // Se puede conservar una asociación existente, incluso si ya no es seleccionable.
            if ($actividadId !== $actual->id_actividad && $destino !== null) {
                Gate::authorize('view', $destino);
            }
            $actual->fill(Arr::except($datos, ['id_actividad']));
            $actual->id_actividad = $actividadId;
            $actual->save();
        });
        return to_route('depurador', ['codigo' => $codigo])->with('aula_status', 'Código actualizado.');
    }

    public function destroy(Codigo $codigo): RedirectResponse
    {
        Gate::authorize('delete', $codigo);
        $this->eliminarRegistro($codigo, 'No se puede eliminar: otros registros utilizan este código.');
        return to_route('aula.codigos.index')->with('aula_status', 'Código eliminado.');
    }

    private function datos(Request $request): array
    {
        return $request->validate([
            'nombre_codigo' => ['required', 'string', 'max:150'],
            'nombre_archivo' => ['required', 'string', 'max:200'],
            'formato' => ['required', 'string', 'max:20'],
            'contenido_codigo' => ['required', 'string', 'max:100000'],
            'id_actividad' => ['present', 'nullable', 'integer', Rule::exists('actividad', 'id_actividad')],
        ]);
    }

    private function autorizarAsociacion(?int $actividadId, bool $bloquear = false): void
    {
        if ($actividadId === null) {
            return;
        }
        $query = Actividad::query();
        if ($bloquear) {
            $query->lockForUpdate();
        }
        Gate::authorize('view', $query->findOrFail($actividadId));
    }

    private function editor(Request $request, ?Codigo $codigo, ?int $actividadId): Response
    {
        return $this->pagina('Aula/Codigos/Edit', [
            'codigo' => $codigo ? $codigo->toArray() + ['huella' => $codigo->huella()] : null,
            'actividades' => Actividad::query()->visiblesPara($request->user())
                ->orderBy('nombre_actividad')->get(['id_actividad', 'nombre_actividad']),
            'inicial' => ['id_actividad' => $actividadId],
        ]);
    }
}
