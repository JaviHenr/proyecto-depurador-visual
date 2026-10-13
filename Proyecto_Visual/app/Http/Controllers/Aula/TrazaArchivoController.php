<?php

namespace App\Http\Controllers\Aula;

use App\Models\Aula\Codigo;
use FilesystemIterator;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use JsonException;

/** Solo lee archivos existentes. No escribe JSON, código ni registros en la base. */
class TrazaArchivoController extends AulaController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Codigo::class);
        $raiz = $this->raiz();
        $archivos = $this->listar($raiz);
        usort($archivos, fn ($a, $b) => strcmp($a['ruta'], $b['ruta']));
        return $this->json(['archivos' => $archivos, 'archivo_inicial' => config('depurador_json.archivo_inicial', 'traza_contador_suma.json')]);
    }

    public function show(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Codigo::class);
        $datos = $request->validate(['archivo' => ['required', 'string', 'max:255']]);
        $archivo = $datos['archivo'];
        // Esta etapa usa una sola carpeta local, sin asociar archivos a registros.
        if ($archivo === '' || str_contains($archivo, '/') || str_contains($archivo, '\\')
            || str_contains($archivo, "\0") || strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) !== 'json') {
            throw ValidationException::withMessages(['archivo' => 'Selecciona un archivo JSON de la lista disponible.']);
        }
        $raiz = $this->raiz();
        $ruta = realpath($raiz.DIRECTORY_SEPARATOR.$archivo);
        abort_unless($ruta && is_file($ruta) && is_readable($ruta) && str_starts_with($ruta, $raiz.DIRECTORY_SEPARATOR), 404, 'El archivo JSON no está disponible.');
        abort_unless(dirname($ruta) === $raiz, 404, 'El archivo JSON no pertenece a la carpeta de trazas.');
        $limite = (int) config('depurador_json.max_bytes', 5 * 1024 * 1024);
        $stream = @fopen($ruta, 'rb');
        abort_if($stream === false, 503, 'No se pudo leer el archivo JSON.');
        try {
            $texto = stream_get_contents($stream, $limite + 1);
        } finally {
            fclose($stream);
        }
        if ($texto === false || strlen($texto) > $limite) {
            throw ValidationException::withMessages(['archivo' => 'El JSON excede el tamaño permitido (5 MiB por defecto).']);
        }
        try {
            $documento = json_decode($texto, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['archivo' => 'El archivo no contiene JSON válido.']);
        }
        if (!is_array($documento) || !isset($documento['@graph']) || !is_array($documento['@graph'])
            || !array_is_list($documento['@graph']) || !count($documento['@graph']) || count($documento['@graph']) > 5000) {
            throw ValidationException::withMessages(['archivo' => 'El JSON debe incluir @graph con entre 1 y 5000 pasos.']);
        }
        // @context y @graph se entregan tal como llegaron. No se añade _meta.
        return $this->json(['archivo' => $archivo, 'traza' => $documento]);
    }

    private function raiz(): string
    {
        $configurado = (string) config('depurador_json.directorio', storage_path('app/trazas'));
        $raiz = realpath($configurado);
        abort_unless($raiz && is_dir($raiz) && is_readable($raiz), 503, 'Crea la carpeta storage/app/trazas y deposita allí los archivos JSON.');
        return $raiz;
    }

    private function listar(string $carpeta): array
    {
        $archivos = [];
        $maximo = (int) config('depurador_json.max_archivos', 200);
        foreach (new FilesystemIterator($carpeta, FilesystemIterator::SKIP_DOTS) as $entrada) {
            $ruta = $entrada->getRealPath();
            if (!$entrada->isFile() || !$ruta || !$entrada->isReadable() || dirname($ruta) !== $carpeta
                || strtolower($entrada->getExtension()) !== 'json') continue;
            $archivos[] = ['nombre' => $entrada->getFilename(), 'ruta' => $entrada->getFilename()];
            if (count($archivos) >= $maximo) break;
        }
        return $archivos;
    }

    private function json(array $datos): JsonResponse
    {
        return response()->json($datos)->header('Cache-Control', 'private, no-store');
    }
}
