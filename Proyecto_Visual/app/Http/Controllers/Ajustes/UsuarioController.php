<?php

namespace App\Http\Controllers\Ajustes;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Usuario::class);
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $filtros['q'] ?? '';
        $usuarios = Usuario::query()
            ->select(['id_usuario', 'nombre_usuario', 'email', 'rol'])
            ->whereIn('rol', ['profesor', 'estudiante'])
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->where(function (Builder $consulta) use ($q): void {
                    $consulta->whereRaw('LOWER(nombre_usuario) LIKE ?', ['%'.mb_strtolower($q).'%'])
                        ->orWhereRaw('LOWER(email) LIKE ?', ['%'.mb_strtolower($q).'%']);
                });
            })
            ->orderBy('nombre_usuario')->orderBy('id_usuario')
            ->paginate(12)->withQueryString();
        $usuarios->through(fn (Usuario $usuario): array => $this->datosPublicos($usuario) + [
            'puede_editar' => Gate::allows('update', $usuario),
            'es_actual' => $usuario->is($request->user()),
        ]);

        return Inertia::render('Ajustes', [
            'usuarios' => $usuarios,
            'filtros' => ['q' => $q],
            'perfilId' => (int) $request->user()->getAuthIdentifier(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Usuario::class);

        return Inertia::render('Ajustes/Usuarios/Edit', ['usuarioEditar' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Usuario::class);
        Usuario::create($this->validarDatos($request));

        return to_route('ajustes')->with('aula_status', 'Usuario creado. Ya puede iniciar sesión con su correo y contraseña.');
    }

    public function edit(Usuario $usuario): Response
    {
        Gate::authorize('update', $usuario);

        return Inertia::render('Ajustes/Usuarios/Edit', ['usuarioEditar' => $this->datosPublicos($usuario)]);
    }

    public function update(Request $request, Usuario $usuario): RedirectResponse
    {
        Gate::authorize('update', $usuario);
        $datos = $this->validarDatos($request, $usuario);
        if (empty($datos['contrasena'])) {
            unset($datos['contrasena']);
        } else {
            $usuario->remember_token = Str::random(60);
        }
        $usuario->fill($datos)->save();

        return to_route('ajustes')->with('aula_status', 'Datos del usuario actualizados.');
    }

    /** @return array{id_usuario: int, nombre_usuario: string, email: string, rol: string} */
    private function datosPublicos(Usuario $usuario): array
    {
        return [
            'id_usuario' => (int) $usuario->getKey(),
            'nombre_usuario' => $usuario->nombre_usuario,
            'email' => $usuario->email,
            'rol' => $usuario->rol,
        ];
    }

    /** @return array{nombre_usuario: string, email: string, rol: string, contrasena?: string|null} */
    private function validarDatos(Request $request, ?Usuario $usuario = null): array
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
        $correoUnico = Rule::unique(Usuario::class, 'email');
        if ($usuario !== null) {
            $correoUnico->ignore($usuario);
        }

        return $request->validate([
            'nombre_usuario' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:200', $correoUnico],
            'rol' => ['required', Rule::in($usuario ? [$usuario->rol] : ['profesor', 'estudiante'])],
            'contrasena' => [$usuario ? 'nullable' : 'required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'nombre_usuario.required' => 'Escribe el nombre del usuario.',
            'nombre_usuario.max' => 'El nombre no puede superar los 100 caracteres.',
            'email.required' => 'Escribe el correo electrónico.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'email.max' => 'El correo no puede superar los 200 caracteres.',
            'email.unique' => 'Ese correo electrónico ya está registrado.',
            'rol.required' => 'Selecciona el rol del usuario.',
            'rol.in' => $usuario ? 'No se puede cambiar el rol de un usuario existente.' : 'Selecciona Profesor o Estudiante.',
            'contrasena.required' => 'Escribe una contraseña para el nuevo usuario.',
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'contrasena.max' => 'La contraseña no puede superar los 72 caracteres.',
            'contrasena.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);
    }
}
