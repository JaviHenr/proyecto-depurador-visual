<?php

use App\Models\Usuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\UsuariosAjustesTestCase;

uses(UsuariosAjustesTestCase::class);

function datosUsuarioAjustes(array $cambios = []): array
{
    return array_replace([
        'nombre_usuario' => 'Nuevo usuario',
        'email' => 'nuevo@example.test',
        'rol' => 'estudiante',
        'contrasena' => 'ClavePruebas123!',
        'contrasena_confirmation' => 'ClavePruebas123!',
    ], $cambios);
}

it('envia al login las rutas de ajustes sin sesion', function (string $metodo, string $ruta): void {
    $this->{$metodo}($ruta)->assertRedirect(route('login'));
})->with([
    'listado' => ['get', '/ajustes'],
    'formulario nuevo' => ['get', '/ajustes/usuarios/nuevo'],
    'crear' => ['post', '/ajustes/usuarios'],
    'formulario editar' => ['get', '/ajustes/usuarios/1/editar'],
    'actualizar' => ['put', '/ajustes/usuarios/1'],
]);

it('bloquea las rutas de ajustes a los estudiantes', function (string $metodo, string $ruta): void {
    $estudiante = UsuarioFactory::new()->estudiante()->create(['id_usuario' => 1]);

    $this->actingAs($estudiante)->{$metodo}($ruta, datosUsuarioAjustes())->assertForbidden();

    $this->assertDatabaseCount('usuario', 1);
})->with([
    'listado' => ['get', '/ajustes'],
    'formulario nuevo' => ['get', '/ajustes/usuarios/nuevo'],
    'crear' => ['post', '/ajustes/usuarios'],
    'formulario editar' => ['get', '/ajustes/usuarios/1/editar'],
    'actualizar' => ['put', '/ajustes/usuarios/1'],
]);

it('muestra el listado con permisos y sin contrasenas ni tokens', function (): void {
    $profesor = UsuarioFactory::new()->profesor()->create(['nombre_usuario' => 'A Profesor']);
    $otro = UsuarioFactory::new()->profesor()->create(['nombre_usuario' => 'B Profesor']);
    $estudiante = UsuarioFactory::new()->estudiante()->create(['nombre_usuario' => 'C Estudiante']);

    $this->actingAs($profesor)->get('/ajustes')->assertInertia(fn (Assert $page) => $page
        ->component('Ajustes')->has('usuarios.data', 3)
        ->where('usuarios.data.0.id_usuario', $profesor->id_usuario)
        ->where('usuarios.data.0.puede_editar', true)->where('usuarios.data.0.es_actual', true)
        ->where('usuarios.data.1.id_usuario', $otro->id_usuario)
        ->where('usuarios.data.1.puede_editar', false)
        ->where('usuarios.data.2.id_usuario', $estudiante->id_usuario)
        ->where('usuarios.data.2.puede_editar', true)
        ->missing('usuarios.data.0.contrasena')->missing('usuarios.data.0.remember_token')
        ->missing('auth.user.contrasena')->missing('auth.user.remember_token'));
});

it('encuentra usuarios por correo sin distinguir mayusculas', function (): void {
    $profesor = UsuarioFactory::new()->profesor()->create();
    $estudiante = UsuarioFactory::new()->estudiante()->create(['email' => 'buscado@example.test']);

    $this->actingAs($profesor)->get('/ajustes?q=BUSCADO')->assertInertia(fn (Assert $page) => $page
        ->where('filtros.q', 'BUSCADO')->has('usuarios.data', 1)
        ->where('usuarios.data.0.id_usuario', $estudiante->id_usuario));
});

it('muestra el formulario para crear y editar datos permitidos', function (): void {
    $profesor = UsuarioFactory::new()->profesor()->create();

    $this->actingAs($profesor)->get('/ajustes/usuarios/nuevo')
        ->assertInertia(fn (Assert $page) => $page->component('Ajustes/Usuarios/Edit')->where('usuarioEditar', null));
    $this->get('/ajustes/usuarios/'.$profesor->id_usuario.'/editar')
        ->assertInertia(fn (Assert $page) => $page->component('Ajustes/Usuarios/Edit')
            ->where('usuarioEditar.id_usuario', $profesor->id_usuario)->missing('usuarioEditar.contrasena'));
});

it('crea una cuenta con clave cifrada y permite iniciar sesion', function (string $rol): void {
    $profesor = UsuarioFactory::new()->profesor()->create();

    $this->actingAs($profesor)->post('/ajustes/usuarios', datosUsuarioAjustes(['rol' => $rol, 'email' => 'NUEVO@example.test']))
        ->assertRedirect(route('ajustes'))->assertSessionHas('aula_status');

    $creado = Usuario::where('email', 'nuevo@example.test')->sole();
    expect($creado->rol)->toBe($rol);
    expect($creado->contrasena)->not->toBe('ClavePruebas123!');
    expect(Hash::check('ClavePruebas123!', $creado->contrasena))->toBeTrue();

    auth()->logout();
    $this->post('/login', ['email' => 'nuevo@example.test', 'contrasena' => 'ClavePruebas123!'])
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($creado);
})->with(['profesor', 'estudiante']);

it('actualiza los datos del profesor autenticado y conserva su clave si queda vacia', function (): void {
    $profesor = UsuarioFactory::new()->profesor()->create();
    $claveAnterior = $profesor->contrasena;

    $this->actingAs($profesor)->put('/ajustes/usuarios/'.$profesor->id_usuario, datosUsuarioAjustes([
        'nombre_usuario' => 'Mi perfil editado', 'rol' => 'profesor', 'contrasena' => '', 'contrasena_confirmation' => '',
    ]))->assertRedirect(route('ajustes'));

    $this->assertDatabaseHas('usuario', ['id_usuario' => $profesor->id_usuario, 'nombre_usuario' => 'Mi perfil editado', 'email' => 'nuevo@example.test', 'rol' => 'profesor']);
    expect($profesor->fresh()->contrasena)->toBe($claveAnterior);
});

it('actualiza el nombre correo y clave de un estudiante y mantiene su rol', function (): void {
    $profesor = UsuarioFactory::new()->profesor()->create();
    $estudiante = UsuarioFactory::new()->estudiante()->create(['remember_token' => 'token-anterior']);

    $this->actingAs($profesor)->put('/ajustes/usuarios/'.$estudiante->id_usuario, datosUsuarioAjustes())
        ->assertRedirect(route('ajustes'));

    $actualizado = $estudiante->fresh();
    $this->assertDatabaseHas('usuario', ['id_usuario' => $estudiante->id_usuario, 'nombre_usuario' => 'Nuevo usuario', 'email' => 'nuevo@example.test', 'rol' => 'estudiante']);
    expect(Hash::check('ClavePruebas123!', $actualizado->contrasena))->toBeTrue();
    expect($actualizado->remember_token)->not->toBe('token-anterior');
});

it('mantiene la identidad del profesor en la navegacion al editar un estudiante', function (): void {
    $profesor = UsuarioFactory::new()->profesor()->create();
    $estudiante = UsuarioFactory::new()->estudiante()->create();

    $this->actingAs($profesor)->get('/ajustes/usuarios/'.$estudiante->id_usuario.'/editar')
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.id_usuario', $profesor->id_usuario)
            ->where('auth.user.rol', 'profesor')
            ->where('usuarioEditar.id_usuario', $estudiante->id_usuario)
            ->missing('usuario')->missing('usuarioEditar.contrasena'));
});

it('rechaza la lectura y modificacion de otro profesor aunque se use su URL', function (): void {
    $profesor = UsuarioFactory::new()->profesor()->create();
    $otro = UsuarioFactory::new()->profesor()->create(['nombre_usuario' => 'Profesor protegido']);
    $original = $otro->fresh()->getAttributes();

    $this->actingAs($profesor)->get('/ajustes/usuarios/'.$otro->id_usuario.'/editar')->assertForbidden();
    $this->put('/ajustes/usuarios/'.$otro->id_usuario, datosUsuarioAjustes(['rol' => 'profesor']))->assertForbidden();

    expect($otro->fresh()->getAttributes())->toBe($original);
});

it('rechaza cambiar el rol de una cuenta existente', function (string $rolOriginal, string $rolNuevo): void {
    $profesor = UsuarioFactory::new()->profesor()->create();
    $destino = $rolOriginal === 'profesor' ? $profesor : UsuarioFactory::new()->estudiante()->create();
    $original = $destino->fresh()->getAttributes();

    $this->actingAs($profesor)->from('/ajustes')->put('/ajustes/usuarios/'.$destino->id_usuario, datosUsuarioAjustes(['rol' => $rolNuevo]))
        ->assertRedirect('/ajustes')->assertSessionHasErrors(['rol' => 'No se puede cambiar el rol de un usuario existente.']);

    expect($destino->fresh()->getAttributes())->toBe($original);
})->with([
    'no promover estudiante' => ['estudiante', 'profesor'],
    'no degradar profesor' => ['profesor', 'estudiante'],
]);

it('valida los datos nuevos sin crear cuentas incompletas', function (array $cambios, string $campo, string $mensaje): void {
    $profesor = UsuarioFactory::new()->profesor()->create(['email' => 'ocupado@example.test']);

    $this->actingAs($profesor)->from('/ajustes/usuarios/nuevo')->post('/ajustes/usuarios', datosUsuarioAjustes($cambios))
        ->assertRedirect('/ajustes/usuarios/nuevo')->assertSessionHasErrors([$campo => $mensaje])
        ->assertSessionMissing('_old_input.contrasena')->assertSessionMissing('_old_input.contrasena_confirmation');

    $this->assertDatabaseCount('usuario', 1);
})->with([
    'nombre requerido' => [['nombre_usuario' => ''], 'nombre_usuario', 'Escribe el nombre del usuario.'],
    'correo requerido' => [['email' => ''], 'email', 'Escribe el correo electrónico.'],
    'correo invalido' => [['email' => 'incorrecto'], 'email', 'Escribe un correo electrónico válido.'],
    'correo duplicado' => [['email' => 'ocupado@example.test'], 'email', 'Ese correo electrónico ya está registrado.'],
    'rol requerido' => [['rol' => ''], 'rol', 'Selecciona el rol del usuario.'],
    'rol desconocido' => [['rol' => 'administrador'], 'rol', 'Selecciona Profesor o Estudiante.'],
    'clave requerida' => [['contrasena' => '', 'contrasena_confirmation' => ''], 'contrasena', 'Escribe una contraseña para el nuevo usuario.'],
    'clave corta' => [['contrasena' => 'corta', 'contrasena_confirmation' => 'corta'], 'contrasena', 'La contraseña debe tener al menos 8 caracteres.'],
    'confirmacion distinta' => [['contrasena_confirmation' => 'OtraClave123!'], 'contrasena', 'La confirmación de la contraseña no coincide.'],
]);

it('ignora atributos que no pertenecen al formulario', function (): void {
    $profesor = UsuarioFactory::new()->profesor()->create();

    $this->actingAs($profesor)->post('/ajustes/usuarios', datosUsuarioAjustes([
        'id_usuario' => 999, 'remember_token' => 'token-inyectado', 'email_verified_at' => '2000-01-01 00:00:00',
    ]))->assertRedirect(route('ajustes'));

    $creado = Usuario::where('email', 'nuevo@example.test')->sole();
    expect($creado->id_usuario)->not->toBe(999);
    expect($creado->remember_token)->toBeNull();
    expect($creado->email_verified_at)->toBeNull();
});

it('conserva un correo propio al editar y rechaza usar el de otra cuenta', function (): void {
    $profesor = UsuarioFactory::new()->profesor()->create(['email' => 'propio@example.test']);
    $otro = UsuarioFactory::new()->estudiante()->create(['email' => 'ocupado@example.test']);

    $this->actingAs($profesor)->put('/ajustes/usuarios/'.$profesor->id_usuario, datosUsuarioAjustes(['email' => 'propio@example.test', 'rol' => 'profesor']))
        ->assertRedirect(route('ajustes'));
    $this->from('/ajustes')->put('/ajustes/usuarios/'.$profesor->id_usuario, datosUsuarioAjustes(['email' => $otro->email, 'rol' => 'profesor']))
        ->assertSessionHasErrors(['email' => 'Ese correo electrónico ya está registrado.']);

    expect($profesor->fresh()->email)->toBe('propio@example.test');
});

it('no ofrece una ruta para eliminar usuarios', function (): void {
    $profesor = UsuarioFactory::new()->profesor()->create();
    $estudiante = UsuarioFactory::new()->estudiante()->create();

    $this->actingAs($profesor)->delete('/ajustes/usuarios/'.$estudiante->id_usuario)->assertMethodNotAllowed();

    $this->assertModelExists($estudiante);
});
