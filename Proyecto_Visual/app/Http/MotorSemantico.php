<?php

namespace App\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class MotorSemantico
{
    public static function huellaTraza(array $traza): string
    {
        return hash('sha256', json_encode($traza, JSON_THROW_ON_ERROR));
    }

    /** @return array{numeroPaso: int, correcto: bool, valorEsperado: int|bool, tipoError: ?string, mensaje: string} */
    public function validar(array $traza, int $numeroPaso, int|bool $respuesta): array
    {
        if (($traza['versionFormato'] ?? null) !== '1.0') {
            throw ValidationException::withMessages([
                'traza' => 'Para predecir se necesita una traza en formato 1.0. Puedes observar esta traza o generar una nueva.',
            ]);
        }

        try {
            $resultado = Http::acceptJson()->asJson()
                ->connectTimeout(3)->timeout((int) config('services.motor_semantico.timeout', 10))
                ->withOptions(['allow_redirects' => false])
                ->post((string) config('services.motor_semantico.url'), compact('traza', 'numeroPaso', 'respuesta'));
        } catch (ConnectionException) {
            abort(503, 'No se pudo conectar con el motor semántico. Vuelve a intentar confirmar el paso.');
        }

        if ($resultado->unprocessableEntity()) {
            $mensaje = $resultado->json('detail.mensaje');

            throw ValidationException::withMessages([
                'respuesta' => is_string($mensaje) && trim($mensaje) !== ''
                    ? mb_substr($mensaje, 0, 500)
                    : 'El motor no pudo validar este paso. Revisa que la respuesta sea un entero o verdadero/falso, según la pregunta.',
            ]);
        }

        abort_unless($resultado->ok(), 502, 'El motor semántico no respondió correctamente. Vuelve a intentar confirmar el paso.');
        $datos = $resultado->json();
        $esperado = $datos['valorEsperado'] ?? null;
        abort_unless(is_array($datos)
            && ($datos['numeroPaso'] ?? null) === $numeroPaso
            && is_bool($datos['correcto'] ?? null)
            && (is_bool($respuesta) ? is_bool($esperado) : is_int($esperado) && abs($esperado) <= 9007199254740991)
            && array_key_exists('tipoError', $datos)
            && ($datos['tipoError'] === null || is_string($datos['tipoError']))
            && is_string($datos['mensaje'] ?? null) && trim($datos['mensaje']) !== '',
            502, 'El motor semántico devolvió una respuesta inválida. Vuelve a intentar confirmar el paso.');

        return [
            'numeroPaso' => $numeroPaso,
            'correcto' => $datos['correcto'],
            'valorEsperado' => $esperado,
            'tipoError' => $datos['tipoError'],
            'mensaje' => mb_substr($datos['mensaje'], 0, 500),
        ];
    }
}
