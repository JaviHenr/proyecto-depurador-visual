<?php

namespace App\Http\Controllers\Aula;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

abstract class AulaController extends Controller
{
    protected function pagina(string $pagina, array $datos): Response
    {
        $usuario = request()->user();
        return Inertia::render($pagina, $datos + ['actor' => [
            'id' => (int) $usuario->getAuthIdentifier(),
            'nombre' => $usuario->nombre_usuario ?? $usuario->name ?? 'Usuario',
            'rol' => $usuario->rol,
        ]]);
    }

    protected function eliminarRegistro(Model $modelo, string $mensaje): void
    {
        try {
            $modelo->delete();
        } catch (QueryException $error) {
            // También cubre que otro usuario haya asociado un registro simultáneamente.
            if (in_array((string) $error->getCode(), ['23000', '23001', '23503'], true)) {
                throw ValidationException::withMessages(['eliminar' => $mensaje]);
            }
            throw $error;
        }
    }
}
