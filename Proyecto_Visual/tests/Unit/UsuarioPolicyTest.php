<?php

use App\Policies\UsuarioPolicy;
use Database\Factories\UsuarioFactory;
use Tests\TestCase;

uses(TestCase::class);

it('reserva el listado y la creacion al profesor', function (string $rol, bool $permitido): void {
    $actor = UsuarioFactory::new()->make(['id_usuario' => 1, 'rol' => $rol]);
    $politica = new UsuarioPolicy;

    expect($politica->viewAny($actor))->toBe($permitido);
    expect($politica->create($actor))->toBe($permitido);
})->with([
    'profesor' => ['profesor', true],
    'estudiante' => ['estudiante', false],
    'otro rol' => ['administrador', false],
]);

it('permite editar solo al profesor sus datos o los de estudiantes', function (string $rolActor, string $rolDestino, int $idDestino, bool $permitido): void {
    $actor = UsuarioFactory::new()->make(['id_usuario' => 1, 'rol' => $rolActor]);
    $destino = UsuarioFactory::new()->make(['id_usuario' => $idDestino, 'rol' => $rolDestino]);

    expect((new UsuarioPolicy)->update($actor, $destino))->toBe($permitido);
})->with([
    'profesor propio' => ['profesor', 'profesor', 1, true],
    'profesor ajeno' => ['profesor', 'profesor', 2, false],
    'estudiante' => ['profesor', 'estudiante', 2, true],
    'estudiante propio' => ['estudiante', 'estudiante', 1, false],
    'estudiante a profesor' => ['estudiante', 'profesor', 2, false],
    'rol ajeno' => ['administrador', 'estudiante', 2, false],
]);
