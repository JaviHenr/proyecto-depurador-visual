<?php

namespace App\Http;

use FilesystemIterator;
use Illuminate\Validation\ValidationException;
use JsonException;

class TrazaArchivo
{
    public const PRACTICA = 'practica_contador_suma_v1.json';

    /** @return list<array{nombre: string, ruta: string}> */
    public function listar(): array
    {
        $archivos = [['nombre' => self::PRACTICA, 'ruta' => self::PRACTICA]];
        $raiz = $this->raiz();
        if ($raiz === null) {
            return $archivos;
        }

        $maximo = (int) config('depurador_json.max_archivos', 200);
        foreach (new FilesystemIterator($raiz, FilesystemIterator::SKIP_DOTS) as $entrada) {
            $ruta = $entrada->getRealPath();
            if (! $entrada->isFile() || ! $ruta || ! $entrada->isReadable() || dirname($ruta) !== $raiz
                || strtolower($entrada->getExtension()) !== 'json' || $entrada->getFilename() === self::PRACTICA) {
                continue;
            }
            $archivos[] = ['nombre' => $entrada->getFilename(), 'ruta' => $entrada->getFilename()];
            if (count($archivos) >= $maximo) {
                break;
            }
        }
        usort($archivos, fn (array $a, array $b): int => strcmp($a['ruta'], $b['ruta']));

        return $archivos;
    }

    /** @return array<string, mixed> */
    public function leer(string $archivo): array
    {
        if ($archivo === '' || str_contains($archivo, '/') || str_contains($archivo, '\\')
            || str_contains($archivo, "\0") || strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) !== 'json') {
            throw ValidationException::withMessages(['archivo' => 'Selecciona un archivo JSON de la lista disponible.']);
        }

        $raiz = $archivo === self::PRACTICA ? realpath(base_path('ejemplo_externo')) : $this->raiz();
        $ruta = $raiz ? realpath($raiz.DIRECTORY_SEPARATOR.$archivo) : false;
        abort_unless($ruta && is_file($ruta) && is_readable($ruta) && dirname($ruta) === $raiz,
            404, 'El archivo JSON no está disponible. Vuelve a cargar la traza.');

        $limite = (int) config('depurador_json.max_bytes', 5 * 1024 * 1024);
        $stream = @fopen($ruta, 'rb');
        abort_if($stream === false, 503, 'No se pudo leer el archivo JSON.');
        try {
            $texto = stream_get_contents($stream, $limite + 1);
        } finally {
            fclose($stream);
        }
        if ($texto === false || strlen($texto) > $limite) {
            throw ValidationException::withMessages(['archivo' => 'El JSON excede el tamaño permitido.']);
        }
        try {
            $documento = json_decode($texto, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['archivo' => 'El archivo no contiene JSON válido.']);
        }
        if (! is_array($documento) || ! isset($documento['@graph']) || ! is_array($documento['@graph'])
            || ! array_is_list($documento['@graph']) || ! count($documento['@graph']) || count($documento['@graph']) > 5000) {
            throw ValidationException::withMessages(['archivo' => 'El JSON debe incluir @graph con entre 1 y 5000 pasos.']);
        }

        return $documento;
    }

    private function raiz(): ?string
    {
        $raiz = realpath((string) config('depurador_json.directorio', storage_path('app/trazas')));

        return $raiz && is_dir($raiz) && is_readable($raiz) ? $raiz : null;
    }
}
