<?php

use App\Models\Aula\Codigo;
use App\Policies\Aula\CodigoPolicy;
use App\Policies\Aula\SeccionPolicy;
use Database\Factories\UsuarioActividadFactory;
use Tests\TestCase;

uses(TestCase::class);

describe('Codigo::huella', function (): void {
    it('genera una huella SHA-256 sin persistir el código', function (): void {
        $codigo = new Codigo(['nombre_codigo' => 'Original', 'contenido_codigo' => 'numero = 5']);

        $huella = $codigo->huella();

        expect($huella)->toHaveLength(64)->toMatch('/^[a-f0-9]{64}$/');
        expect($codigo->exists)->toBeFalse();
    });

    it('mantiene la huella mientras no cambie el código', function (): void {
        $codigo = new Codigo(['contenido_codigo' => 'numero = 5']);
        $primera = $codigo->huella();

        expect($codigo->huella())->toBe($primera);
    });

    it('cambia la huella al modificar el contenido', function (): void {
        $codigo = new Codigo(['contenido_codigo' => 'numero = 5']);
        $primera = $codigo->huella();

        $codigo->contenido_codigo = 'numero = 6';

        expect($codigo->huella())->not->toBe($primera);
    });
});

it('valida el acceso al listado según el rol', function (string $rol, bool $permitido): void {
    $usuario = UsuarioActividadFactory::new()->make(['rol' => $rol]);

    expect((new CodigoPolicy)->viewAny($usuario))->toBe($permitido);
})->with([
    'profesor' => ['profesor', true],
    'estudiante' => ['estudiante', true],
    'rol ajeno' => ['invitado', false],
]);

it('valida quién puede actualizar un código', function (string $rol, ?int $autor, bool $permitido): void {
    $usuario = UsuarioActividadFactory::new()->make(['id_usuario' => 7, 'rol' => $rol]);
    $codigo = new Codigo;
    $codigo->id_usuario = $autor;

    expect((new CodigoPolicy)->update($usuario, $codigo))->toBe($permitido);
})->with([
    'autor estudiante' => ['estudiante', 7, true],
    'otro usuario' => ['estudiante', 8, false],
    'sin autor' => ['profesor', null, false],
    'autor con rol no permitido' => ['invitado', 7, false],
]);

it('permite crear secciones solo al profesor', function (string $rol, bool $permitido): void {
    $usuario = UsuarioActividadFactory::new()->make(['rol' => $rol]);

    expect((new SeccionPolicy)->create($usuario))->toBe($permitido);
})->with([
    'profesor' => ['profesor', true],
    'estudiante' => ['estudiante', false],
    'rol ajeno' => ['invitado', false],
]);
