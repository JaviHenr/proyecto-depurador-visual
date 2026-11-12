<?php

namespace App\Http\Controllers\Aula;

use App\Http\MotorSemantico;
use App\Http\TrazaArchivo;
use App\Models\Aula\Codigo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TrazaArchivoController extends AulaController
{
    public function index(TrazaArchivo $trazas): JsonResponse
    {
        Gate::authorize('viewAny', Codigo::class);

        return $this->json([
            'archivos' => $trazas->listar(),
            'archivo_inicial' => config('depurador_json.archivo_inicial', TrazaArchivo::PRACTICA),
        ]);
    }

    public function show(Request $request, TrazaArchivo $trazas): JsonResponse
    {
        Gate::authorize('viewAny', Codigo::class);
        $datos = $request->validate(['archivo' => ['required', 'string', 'max:255']]);
        $traza = $trazas->leer($datos['archivo']);

        return $this->json([
            'archivo' => $datos['archivo'],
            'traza' => $traza,
            'huella_traza' => MotorSemantico::huellaTraza($traza),
        ]);
    }

    private function json(array $datos): JsonResponse
    {
        return response()->json($datos)->header('Cache-Control', 'private, no-store');
    }
}
