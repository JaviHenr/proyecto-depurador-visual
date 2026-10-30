<?php

use App\Models\Aula\Codigo;
use Database\Factories\UsuarioParserFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Support\ParserAstTestCase;

uses(ParserAstTestCase::class);

function fuenteParser(): string
{
    return "contador = 0\nsuma = 0\nwhile contador < 3:\n    contador += 1\n    suma += contador";
}

function trazaParser(): array
{
    return json_decode(file_get_contents(base_path('tests/Fixtures/actividad/parser_contador_v1.json')), true);
}

function datosParser(): array
{
    return [
        'nombre_codigo' => 'Contador y suma', 'nombre_archivo' => 'contador.py',
        'formato' => 'py', 'contenido_codigo' => fuenteParser(), 'id_actividad' => null,
    ];
}

function codigoParser(int $autor): Codigo
{
    $codigo = new Codigo(datosParser());
    $codigo->id_usuario = $autor;
    $codigo->fecha_creacion_codigo = '2026-10-26';
    $codigo->traza_json = trazaParser();
    $codigo->save();

    return $codigo;
}

it('guarda la traza devuelta por el parser junto al codigo e ignora JSON enviado por el navegador', function (): void {
    Http::preventStrayRequests();
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $traza = trazaParser();
    Http::fake([config('services.parser_ast.url') => Http::response($traza)]);

    $respuesta = $this->actingAs($usuario)->post('/codigos', datosParser() + ['traza_json' => ['falsa' => true]]);

    $codigo = Codigo::sole();
    $respuesta->assertSessionHasNoErrors()->assertRedirect(route('depurador', ['codigo' => $codigo]));
    expect($codigo->traza_json)->toBe($traza);
    $this->assertDatabaseHas('codigo', ['id_usuario' => $usuario->getKey(), 'contenido_codigo' => fuenteParser()]);
    Http::assertSent(fn (Request $r): bool => $r->url() === config('services.parser_ast.url')
        && $r->method() === 'POST' && $r->data() === ['codigo' => fuenteParser()]);
});

it('sustituye fuente y traza en una actualizacion y las recupera del codigo guardado', function (): void {
    Http::preventStrayRequests();
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoParser($usuario->getKey());
    $nueva = trazaParser();
    $nueva['@graph'] = [$nueva['@graph'][0]];
    $nueva['@graph'][0]['instruccion'] = 'contador = 9';
    $nueva['@graph'][0]['modifica']['valorNuevo'] = 9;
    Http::fake([config('services.parser_ast.url') => Http::response($nueva)]);

    $this->actingAs($usuario)->put('/codigos/'.$codigo->getKey(), array_replace(datosParser(), [
        'contenido_codigo' => 'contador = 9', 'huella' => $codigo->huella(),
    ]))->assertSessionHasNoErrors()->assertRedirect();

    expect($codigo->fresh()->contenido_codigo)->toBe('contador = 9');
    expect($codigo->fresh()->traza_json)->toBe($nueva);
    $this->assertDatabaseCount('codigo', 1);
    Http::assertSent(fn (Request $r): bool => $r['codigo'] === 'contador = 9');
    config(['inertia.ssr.enabled' => false]);
    $this->get('/depurador/'.$codigo->getKey())->assertOk()
        ->assertViewHas('page', fn (array $page): bool => $page['props']['codigo']['traza_json'] === $nueva);
});

it('rechaza otros formatos antes de llamar al parser', function (): void {
    Http::preventStrayRequests();
    $usuario = UsuarioParserFactory::new()->estudiante()->create();

    $this->actingAs($usuario)->postJson('/codigos', array_replace(datosParser(), [
        'formato' => 'java', 'nombre_archivo' => 'Main.java',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['formato', 'nombre_archivo']);

    $this->assertDatabaseCount('codigo', 0);
    Http::assertNothingSent();
});

it('rechaza programas que superan el limite de 5000 caracteres sin llamar al parser', function (): void {
    Http::preventStrayRequests();
    $usuario = UsuarioParserFactory::new()->estudiante()->create();

    $this->actingAs($usuario)->postJson('/codigos', array_replace(datosParser(), [
        'contenido_codigo' => str_repeat('x', 5001),
    ]))->assertUnprocessable()->assertJsonValidationErrors('contenido_codigo');

    $this->assertDatabaseCount('codigo', 0);
    Http::assertNothingSent();
});

it('conserva fuente y traza anteriores si el parser falla', function (int $estado): void {
    Http::preventStrayRequests();
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoParser($usuario->getKey());
    Http::fake([config('services.parser_ast.url') => Http::response(['detail' => 'Error de prueba'], $estado)]);

    $this->actingAs($usuario)->putJson('/codigos/'.$codigo->getKey(), array_replace(datosParser(), [
        'contenido_codigo' => 'contador = 9', 'huella' => $codigo->huella(),
    ]))->assertUnprocessable()->assertJsonValidationErrors('contenido_codigo');

    expect($codigo->fresh()->contenido_codigo)->toBe(fuenteParser());
    expect($codigo->fresh()->traza_json)->toBe(trazaParser());
    Http::assertSentCount(1);
})->with([422, 500]);

it('informa desconexion y no crea un codigo incompleto', function (): void {
    Http::preventStrayRequests();
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    Http::fake([config('services.parser_ast.url') => Http::failedConnection()]);

    $this->actingAs($usuario)->postJson('/codigos', datosParser())
        ->assertUnprocessable()->assertJsonValidationErrors('traza_json');

    $this->assertDatabaseCount('codigo', 0);
    Http::assertSentCount(1);
});

it('no persiste una respuesta sin traza valida', function (string $tipo): void {
    Http::preventStrayRequests();
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $traza = trazaParser();
    if ($tipo === 'version') {
        $traza['versionFormato'] = '2.0';
    }
    if ($tipo === 'orden') {
        $traza['@graph'][0]['numeroPaso'] = 2;
    }
    if ($tipo === 'condicion') {
        unset($traza['@graph'][2]['resultado']);
    }
    Http::fake([config('services.parser_ast.url') => Http::response($tipo === 'sin-json' ? 'no es JSON' : $traza)]);

    $this->actingAs($usuario)->postJson('/codigos', datosParser())
        ->assertUnprocessable()->assertJsonValidationErrors('traza_json');

    $this->assertDatabaseCount('codigo', 0);
    Http::assertSentCount(1);
})->with(['version', 'orden', 'condicion', 'sin-json']);

it('mantiene campos nuevos de una version menor compatible', function (): void {
    Http::preventStrayRequests();
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $traza = trazaParser();
    $traza['versionFormato'] = '1.1';
    $traza['campoFuturo'] = ['valor' => true];
    Http::fake([config('services.parser_ast.url') => Http::response($traza)]);

    $this->actingAs($usuario)->post('/codigos', datosParser())->assertSessionHasNoErrors();

    expect(Codigo::sole()->traza_json)->toBe($traza);
    Http::assertSentCount(1);
});

it('rechaza una huella desactualizada antes de generar otra traza', function (): void {
    Http::preventStrayRequests();
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoParser($usuario->getKey());

    $this->actingAs($usuario)->putJson('/codigos/'.$codigo->getKey(), datosParser() + ['huella' => str_repeat('0', 64)])
        ->assertUnprocessable()->assertJsonValidationErrors('huella');

    expect($codigo->fresh()->traza_json)->toBe(trazaParser());
    Http::assertNothingSent();
});

it('impide generar o consultar la traza privada de otro estudiante', function (): void {
    Http::preventStrayRequests();
    $autor = UsuarioParserFactory::new()->estudiante()->create();
    $otro = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoParser($autor->getKey());

    $this->actingAs($otro)->putJson('/codigos/'.$codigo->getKey(), datosParser() + ['huella' => $codigo->huella()])->assertForbidden();
    $this->get('/depurador/'.$codigo->getKey())->assertForbidden();

    expect($codigo->fresh()->traza_json)->toBe(trazaParser());
    Http::assertNothingSent();
});

it('requiere sesion antes de solicitar una traza al parser', function (): void {
    Http::preventStrayRequests();

    $this->post('/codigos', datosParser())->assertRedirect('/login');

    $this->assertDatabaseCount('codigo', 0);
    Http::assertNothingSent();
});

it('agrega la columna de traza conservando un codigo existente', function (): void {
    $usuario = UsuarioParserFactory::new()->estudiante()->create();
    $codigo = codigoParser($usuario->getKey());
    Schema::table('codigo', function (Blueprint $table): void {
        $table->dropColumn('traza_json');
    });
    $migracion = require database_path('migrations/2026_10_26_205435_agregar_traza_json_a_codigo.php');

    $migracion->up();

    expect($codigo->fresh()->traza_json)->toBeNull();
    $this->assertDatabaseHas('codigo', [
        'id_codigo' => $codigo->getKey(), 'contenido_codigo' => fuenteParser(), 'id_usuario' => $usuario->getKey(),
    ]);
    $this->assertDatabaseCount('codigo', 1);
});
