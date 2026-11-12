<?php

use App\Http\MotorSemantico;
use App\Http\TrazaArchivo;
use App\Models\Aula\Codigo;
use Database\Factories\UsuarioParserFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\ParserAstTestCase;

uses(ParserAstTestCase::class);

function codigoMotor(int $autor): Codigo
{
    $codigo = new Codigo([
        'nombre_codigo' => 'Contador', 'nombre_archivo' => 'contador.py', 'formato' => 'py',
        'contenido_codigo' => "contador = 0\nsuma = 0\nwhile contador < 3:\n    contador += 1\n    suma += contador",
    ]);
    $codigo->id_usuario = $autor;
    $codigo->fecha_creacion_codigo = '2026-11-12';
    $codigo->traza_json = json_decode(file_get_contents(base_path('tests/Fixtures/actividad/parser_contador_v1.json')), true, flags: JSON_THROW_ON_ERROR);
    $codigo->save();

    return $codigo;
}

function respuestaMotor(Codigo $codigo, int $paso = 4, mixed $respuesta = 1): array
{
    return [
        'codigo' => $codigo->getKey(), 'huella_traza' => MotorSemantico::huellaTraza($codigo->traza_json),
        'numeroPaso' => $paso, 'respuesta' => $respuesta,
    ];
}

it('envia el entero y la traza guardada al motor aunque el navegador intente sustituirla', function (): void {
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoMotor($usuario->getKey());
    $traza = $codigo->traza_json;
    $resultado = ['numeroPaso' => 4, 'correcto' => true, 'valorEsperado' => 1, 'tipoError' => null, 'mensaje' => 'El contador vale 1.'];
    Http::preventStrayRequests();
    Http::fake([config('services.motor_semantico.url') => Http::response($resultado)]);

    $this->actingAs($usuario)->postJson(route('depurador.validar'), respuestaMotor($codigo) + ['traza' => ['falsa' => true]])
        ->assertOk()->assertExactJson($resultado);

    Http::assertSent(fn (Request $r): bool => $r->method() === 'POST'
        && $r->url() === config('services.motor_semantico.url')
        && $r->data() === ['traza' => $traza, 'numeroPaso' => 4, 'respuesta' => 1]);
    expect($codigo->fresh()->traza_json)->toBe($traza);
});

it('envia un booleano y conserva el resultado del motor para una condicion', function (int $paso, bool $respuesta, bool $correcto, bool $esperado, ?string $tipo): void {
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoMotor($usuario->getKey());
    $resultado = ['numeroPaso' => $paso, 'correcto' => $correcto, 'valorEsperado' => $esperado, 'tipoError' => $tipo, 'mensaje' => 'Retroalimentación de la condición.'];
    Http::preventStrayRequests();
    Http::fake([config('services.motor_semantico.url') => Http::response($resultado)]);

    $this->actingAs($usuario)->postJson(route('depurador.validar'), respuestaMotor($codigo, $paso, $respuesta))
        ->assertOk()->assertExactJson($resultado);

    Http::assertSent(fn (Request $r): bool => $r['respuesta'] === $respuesta && $r['numeroPaso'] === $paso);
})->with([
    'primera condición incorrecta' => [3, false, false, true, 'CondicionErronea'],
    'condición final incorrecta' => [12, true, false, false, 'DesfaseDeConteo'],
    'condición final correcta' => [12, false, true, false, null],
]);

it('permite usar la practica incluida sin una carpeta de trazas externas', function (): void {
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    config(['depurador_json.directorio' => storage_path('app/carpeta-no-existente')]);
    $this->actingAs($usuario)->getJson(route('depurador.json.archivos'))
        ->assertOk()->assertJsonPath('archivo_inicial', TrazaArchivo::PRACTICA);
    $archivo = $this->getJson(route('depurador.json.archivo', ['archivo' => TrazaArchivo::PRACTICA]))
        ->assertOk()->assertJsonPath('traza.versionFormato', '1.0')->json();
    Http::preventStrayRequests();
    Http::fake([config('services.motor_semantico.url') => Http::response([
        'numeroPaso' => 3, 'correcto' => true, 'valorEsperado' => true, 'tipoError' => null, 'mensaje' => 'La condición es verdadera.',
    ])]);

    $this->postJson(route('depurador.validar'), [
        'archivo' => TrazaArchivo::PRACTICA, 'huella_traza' => $archivo['huella_traza'], 'numeroPaso' => 3, 'respuesta' => true,
    ])->assertOk()->assertJsonPath('correcto', true);

    Http::assertSent(fn (Request $r): bool => $r['traza'] === $archivo['traza']);
});

it('exige autenticacion y no consulta el motor para un codigo ajeno', function (): void {
    $autor = UsuarioParserFactory::new()->estudiante()->create();
    $otro = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoMotor($autor->getKey());
    Http::preventStrayRequests();

    $this->postJson(route('depurador.validar'), respuestaMotor($codigo))->assertUnauthorized();
    $this->actingAs($otro)->postJson(route('depurador.validar'), respuestaMotor($codigo))->assertForbidden();

    Http::assertNothingSent();
});

it('rechaza respuestas que no sean enteros o booleanos JSON', function (mixed $respuesta): void {
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoMotor($usuario->getKey());
    Http::preventStrayRequests();

    $this->actingAs($usuario)->postJson(route('depurador.validar'), respuestaMotor($codigo, 4, $respuesta))
        ->assertUnprocessable()->assertJsonValidationErrors('respuesta');

    Http::assertNothingSent();
})->with(['texto' => ['1'], 'decimal' => [1.5], 'nulo' => [null]]);

it('pide recargar cuando la traza cambio antes de confirmar', function (): void {
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoMotor($usuario->getKey());
    Http::preventStrayRequests();

    $this->actingAs($usuario)->postJson(route('depurador.validar'), array_replace(respuestaMotor($codigo), ['huella_traza' => str_repeat('0', 64)]))
        ->assertConflict()->assertJsonPath('message', 'La traza cambió. Vuelve a cargarla antes de continuar.');

    Http::assertNothingSent();
});

it('rechaza rutas de archivo fuera de la carpeta de trazas', function (): void {
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    Http::preventStrayRequests();

    $this->actingAs($usuario)->postJson(route('depurador.validar'), [
        'archivo' => '../fuera.json', 'huella_traza' => str_repeat('0', 64), 'numeroPaso' => 1, 'respuesta' => 0,
    ])->assertUnprocessable()->assertJsonValidationErrors('archivo');

    Http::assertNothingSent();
});

it('conserva el mensaje del motor cuando el tipo de respuesta no corresponde al paso', function (): void {
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoMotor($usuario->getKey());
    $mensaje = 'El paso 3 espera verdadero o falso';
    Http::preventStrayRequests();
    Http::fake([config('services.motor_semantico.url') => Http::response(['detail' => ['tipo' => 'TipoDeRespuestaInvalido', 'mensaje' => $mensaje]], 422)]);

    $this->actingAs($usuario)->postJson(route('depurador.validar'), respuestaMotor($codigo, 3, 1))
        ->assertUnprocessable()->assertJsonPath('errors.respuesta.0', $mensaje);

    Http::assertSentCount(1);
});

it('permite reintentar cuando no se puede conectar con el motor', function (): void {
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoMotor($usuario->getKey());
    Http::preventStrayRequests();
    Http::fake([config('services.motor_semantico.url') => Http::failedConnection()]);

    $this->actingAs($usuario)->postJson(route('depurador.validar'), respuestaMotor($codigo))
        ->assertServiceUnavailable()->assertJsonPath('message', 'No se pudo conectar con el motor semántico. Vuelve a intentar confirmar el paso.');
});

it('rechaza un veredicto que el motor devolvio para otro paso', function (): void {
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoMotor($usuario->getKey());
    Http::preventStrayRequests();
    Http::fake([config('services.motor_semantico.url') => Http::response([
        'numeroPaso' => 5, 'correcto' => true, 'valorEsperado' => 1, 'tipoError' => null, 'mensaje' => 'Bien.',
    ])]);

    $this->actingAs($usuario)->postJson(route('depurador.validar'), respuestaMotor($codigo))->assertStatus(502);

    Http::assertSentCount(1);
});
