<?php

namespace App\Http\Controllers\Aula;

use App\Http\MotorSemantico;
use App\Http\TrazaArchivo;
use App\Models\Aula\Codigo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ValidacionSemanticaController extends AulaController
{
    public function store(Request $request, MotorSemantico $motor, TrazaArchivo $trazas): JsonResponse
    {
        Gate::authorize('viewAny', Codigo::class);
        $datos = $request->validate([
            'codigo' => ['required_without:archivo', 'prohibits:archivo', 'integer', 'min:1'],
            'archivo' => ['required_without:codigo', 'prohibits:codigo', 'string', 'max:255'],
            'huella_traza' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'numeroPaso' => ['required', 'integer', 'between:1,5000'],
            'respuesta' => ['present'],
        ]);
        $respuesta = $datos['respuesta'];
        if (! is_bool($respuesta) && (! is_int($respuesta) || abs($respuesta) > 9007199254740991)) {
            throw ValidationException::withMessages(['respuesta' => 'Selecciona verdadero o falso, o escribe un número entero válido según el paso.']);
        }

        if (isset($datos['codigo'])) {
            $codigo = Codigo::findOrFail($datos['codigo']);
            Gate::authorize('view', $codigo);
            $traza = $codigo->traza_json;
            if (! is_array($traza)) {
                throw ValidationException::withMessages(['traza' => 'Guarda el programa para generar su traza antes de responder.']);
            }
        } else {
            $traza = $trazas->leer($datos['archivo']);
        }

        abort_unless(hash_equals(MotorSemantico::huellaTraza($traza), $datos['huella_traza']),
            409, 'La traza cambió. Vuelve a cargarla antes de continuar.');

        return response()->json($motor->validar($traza, (int) $datos['numeroPaso'], $respuesta))
            ->header('Cache-Control', 'private, no-store');
    }
}
