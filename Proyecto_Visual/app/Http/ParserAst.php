<?php

namespace App\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ParserAst
{
    /** @return array<string, mixed> */
    public function generar(string $codigo): array
    {
        try {
            $respuesta = Http::acceptJson()->asJson()
                ->connectTimeout(5)->timeout((int) config('services.parser_ast.timeout', 30))
                ->withOptions(['allow_redirects' => false])
                ->post((string) config('services.parser_ast.url'), ['codigo' => $codigo]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'traza_json' => 'No se pudo conectar con el Parser-AST. Tus cambios siguen en el editor; vuelve a intentar guardar.',
            ]);
        }

        if (! $respuesta->successful()) {
            $detalle = $respuesta->json('detail');
            throw ValidationException::withMessages([
                'contenido_codigo' => $respuesta->status() === 422
                    ? 'El Parser-AST rechazó el programa Python. '.(is_string($detalle) ? mb_substr($detalle, 0, 500) : 'Revisa la sintaxis y las instrucciones admitidas.')
                    : 'El Parser-AST no pudo generar la traza. Intenta guardar nuevamente.',
            ]);
        }

        $traza = $respuesta->json();
        if (strlen($respuesta->body()) > 5 * 1024 * 1024 || ! is_array($traza)) {
            $this->rechazar();
        }
        $validacion = Validator::make($traza, [
            '@context' => ['required'],
            'versionFormato' => ['required', 'string', 'regex:/^1\.[0-9]+$/'],
            '@graph' => ['required', 'array', 'list', 'min:1', 'max:5000'],
            '@graph.*.@type' => ['required', 'in:PasoDeEjecucion'],
            '@graph.*.numeroPaso' => ['required', 'integer', 'min:1'],
            '@graph.*.linea' => ['required', 'integer', 'min:1'],
            '@graph.*.bloque' => ['present', 'nullable', 'string'],
            '@graph.*.iteracion' => ['present', 'nullable', 'integer', 'min:0'],
            '@graph.*.instruccion' => ['required', 'string'],
        ]);
        if ($validacion->fails()) {
            $this->rechazar();
        }
        $lineas = count(explode("\n", str_replace(["\r\n", "\r"], "\n", $codigo)));
        foreach ($traza['@graph'] as $indice => $paso) {
            if ($paso['numeroPaso'] !== $indice + 1 || ! is_int($paso['linea']) || $paso['linea'] > $lineas) {
                $this->rechazar();
            }
            if (isset($paso['modifica']) === isset($paso['evalua'])) {
                $this->rechazar();
            }
            if (isset($paso['modifica'])) {
                $cambio = $paso['modifica'];
                if (! is_array($cambio) || ($cambio['@type'] ?? null) !== 'EstadoDeMemoria'
                    || ! is_string($cambio['variable'] ?? null) || $cambio['variable'] === ''
                    || ($cambio['ambito'] ?? null) !== 'global'
                    || ! array_key_exists('valorAnterior', $cambio) || ! array_key_exists('valorNuevo', $cambio)) {
                    $this->rechazar();
                }
            } else {
                $condicion = $paso['evalua'];
                if (! is_array($condicion) || ($condicion['@type'] ?? null) !== 'Condicion'
                    || ! is_string($condicion['@id'] ?? null) || ! is_string($condicion['expresion'] ?? null)
                    || ! is_array($condicion['dependeDe'] ?? null) || ! is_bool($paso['resultado'] ?? null)) {
                    $this->rechazar();
                }
            }
        }

        return $traza;
    }

    private function rechazar(): never
    {
        throw ValidationException::withMessages([
            'traza_json' => 'El Parser-AST devolvió una traza inválida o de una versión no compatible. El código y su traza anterior se conservaron.',
        ]);
    }
}
