<?php

use App\Models\Aula\Codigo;
use Database\Factories\UsuarioActividadFactory;
use Illuminate\Support\Facades\File;
use Tests\Support\ActividadTestCase;

uses(ActividadTestCase::class);

function datosCodigoActividad(): array
{
    return [
        'nombre_codigo' => 'Factorial de prueba', 'nombre_archivo' => 'factorial_5.py',
        'formato' => 'py', 'contenido_codigo' => "numero = 5\nfactorial = 1\nwhile numero > 1:\n    factorial *= numero\n    numero -= 1",
        'id_actividad' => null,
    ];
}

it('exige sesión en los cuatro endpoints', function (string $metodo, string $ruta): void {
    $this->{$metodo}($ruta)->assertRedirect('/login');
})->with([
    'lista de JSON' => ['get', '/depurador/trazas/archivos'],
    'lectura de JSON' => ['get', '/depurador/trazas/archivo?archivo=traza_factorial_5.json'],
    'creación de código' => ['post', '/codigos'],
    'actualización de código' => ['put', '/codigos/1'],
]);

it('lista solo archivos JSON', function (): void {
    $usuario = UsuarioActividadFactory::new()->estudiante()->create();
    File::put($this->carpetaTrazas.DIRECTORY_SEPARATOR.'ignorar.txt', 'texto');

    $this->actingAs($usuario)->getJson('/depurador/trazas/archivos')
        ->assertOk()->assertJsonCount(1, 'archivos')
        ->assertJsonPath('archivos.0.nombre', 'traza_factorial_5.json');
});

it('devuelve la traza de 15 pasos sin alterar el JSON', function (): void {
    $usuario = UsuarioActividadFactory::new()->estudiante()->create();
    $original = json_decode(File::get(base_path('tests/Fixtures/actividad/traza_factorial_5.json')), true);

    $this->actingAs($usuario)->getJson('/depurador/trazas/archivo?archivo=traza_factorial_5.json')
        ->assertOk()->assertJsonPath('archivo', 'traza_factorial_5.json')
        ->assertJsonCount(15, 'traza.@graph')->assertJsonPath('traza', $original);
});

it('responde 404 para un JSON inexistente', function (): void {
    $usuario = UsuarioActividadFactory::new()->estudiante()->create();

    $this->actingAs($usuario)->getJson('/depurador/trazas/archivo?archivo=no_existe.json')
        ->assertNotFound();
});

it('responde 422 al intentar salir de la carpeta de trazas', function (): void {
    $usuario = UsuarioActividadFactory::new()->estudiante()->create();

    $this->actingAs($usuario)->getJson('/depurador/trazas/archivo?archivo=..%2Fsecreto.json')
        ->assertUnprocessable()->assertJsonValidationErrors('archivo');
});

it('crea un código asignando el autor de la sesión', function (): void {
    $usuario = UsuarioActividadFactory::new()->estudiante()->create();
    $datos = datosCodigoActividad();

    $respuesta = $this->actingAs($usuario)->post('/codigos', $datos);

    $respuesta->assertSessionHasNoErrors();
    $codigo = Codigo::sole();
    $respuesta->assertRedirect(route('depurador', ['codigo' => $codigo]));
    $this->assertDatabaseHas('codigo', [
        'id_codigo' => $codigo->getKey(), 'id_usuario' => $usuario->getKey(),
        'contenido_codigo' => $datos['contenido_codigo'],
    ]);
});

it('responde 422 y no guarda un código sin contenido', function (): void {
    $usuario = UsuarioActividadFactory::new()->estudiante()->create();
    $datos = array_replace(datosCodigoActividad(), ['contenido_codigo' => '']);

    $this->actingAs($usuario)->postJson('/codigos', $datos)
        ->assertUnprocessable()->assertJsonValidationErrors('contenido_codigo');

    $this->assertDatabaseCount('codigo', 0);
});

it('actualiza un código propio conservando su autor y su identidad', function (): void {
    $usuario = UsuarioActividadFactory::new()->estudiante()->create();
    $codigo = new Codigo(array_diff_key(datosCodigoActividad(), ['id_actividad' => true]));
    $codigo->id_usuario = $usuario->getKey();
    $codigo->fecha_creacion_codigo = '2026-10-19';
    $codigo->save();
    $datos = array_replace(datosCodigoActividad(), [
        'nombre_codigo' => 'Actualizado', 'huella' => $codigo->huella(),
    ]);

    $this->actingAs($usuario)->put('/codigos/'.$codigo->getKey(), $datos)
        ->assertSessionHasNoErrors()->assertRedirect(route('depurador', ['codigo' => $codigo]));

    $this->assertDatabaseCount('codigo', 1);
    $this->assertDatabaseHas('codigo', [
        'id_codigo' => $codigo->getKey(), 'nombre_codigo' => 'Actualizado', 'id_usuario' => $usuario->getKey(),
    ]);
});

it('responde 403 cuando otro estudiante intenta modificar el código', function (): void {
    $autor = UsuarioActividadFactory::new()->estudiante()->create();
    $otro = UsuarioActividadFactory::new()->estudiante()->create();
    $codigo = new Codigo(array_diff_key(datosCodigoActividad(), ['id_actividad' => true]));
    $codigo->id_usuario = $autor->getKey();
    $codigo->fecha_creacion_codigo = '2026-10-19';
    $codigo->save();
    $datos = array_replace(datosCodigoActividad(), [
        'nombre_codigo' => 'Cambio ajeno', 'huella' => $codigo->huella(),
    ]);

    $this->actingAs($otro)->putJson('/codigos/'.$codigo->getKey(), $datos)->assertForbidden();

    expect($codigo->fresh()->nombre_codigo)->toBe('Factorial de prueba');
});

it('responde 422 y conserva el código cuando la huella está desactualizada', function (): void {
    $usuario = UsuarioActividadFactory::new()->estudiante()->create();
    $codigo = new Codigo(array_diff_key(datosCodigoActividad(), ['id_actividad' => true]));
    $codigo->id_usuario = $usuario->getKey();
    $codigo->fecha_creacion_codigo = '2026-10-19';
    $codigo->save();
    $datos = array_replace(datosCodigoActividad(), [
        'nombre_codigo' => 'No debe guardarse', 'huella' => str_repeat('0', 64),
    ]);

    $this->actingAs($usuario)->putJson('/codigos/'.$codigo->getKey(), $datos)
        ->assertUnprocessable()->assertJsonValidationErrors('huella');

    expect($codigo->fresh()->nombre_codigo)->toBe('Factorial de prueba');
});
