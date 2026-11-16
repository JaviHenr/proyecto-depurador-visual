<?php

use App\Models\Aula\Seccion;
use Database\Factories\UsuarioFactory;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ParserAstTestCase;

uses(ParserAstTestCase::class);

it('lista estudiantes disponibles, filtra por nombre o correo y oculta la lista a estudiantes', function (): void {
    $profesor = UsuarioFactory::new()->profesor()->create(['nombre_usuario' => 'Teresa Docente']);
    $inscrito = UsuarioFactory::new()->estudiante()->create(['nombre_usuario' => 'Ana Inscrita']);
    $disponible = UsuarioFactory::new()->estudiante()->create(['nombre_usuario' => 'Beto Disponible', 'email' => 'beto@example.test']);
    $seccion = Seccion::forceCreate(['id_usuario' => $profesor->getKey(), 'nombre_seccion' => 'Programación']);
    $seccion->estudiantes()->attach($inscrito->getKey());
    $ruta = route('aula.secciones.show', $seccion);

    $this->actingAs($profesor)->get($ruta)->assertInertia(fn (Assert $page) => $page
        ->has('estudiantesDisponibles.data', 1)
        ->where('estudiantesDisponibles.data.0.id_usuario', $disponible->getKey())
        ->missing('estudiantesDisponibles.data.0.contrasena'));
    foreach (['disponible', '@EXAMPLE.TEST'] as $busqueda) {
        $this->get($ruta.'?'.http_build_query(['buscar_estudiante' => $busqueda]))
            ->assertInertia(fn (Assert $page) => $page->where('estudiantesDisponibles.data.0.id_usuario', $disponible->getKey()));
    }
    $this->get($ruta.'?buscar_estudiante=ausente')
        ->assertInertia(fn (Assert $page) => $page->has('estudiantesDisponibles.data', 0));

    $this->actingAs($inscrito)->get($ruta)
        ->assertInertia(fn (Assert $page) => $page->where('estudiantesDisponibles', null));
});

it('inscribe por id solo a estudiantes y solo si el profesor gestiona la sección', function (): void {
    $profesor = UsuarioFactory::new()->profesor()->create();
    $otroProfesor = UsuarioFactory::new()->profesor()->create();
    $estudiante = UsuarioFactory::new()->estudiante()->create();
    $seccion = Seccion::forceCreate(['id_usuario' => $profesor->getKey(), 'nombre_seccion' => 'Programación']);
    $ruta = route('aula.estudiantes.store', $seccion);

    $this->actingAs($otroProfesor)->post($ruta, ['id_estudiante' => $estudiante->getKey()])->assertForbidden();
    $this->actingAs($estudiante)->post($ruta, ['id_estudiante' => $estudiante->getKey()])->assertForbidden();
    $this->actingAs($profesor)->post($ruta, ['id_estudiante' => $otroProfesor->getKey()])->assertSessionHasErrors('id_estudiante');
    $this->post($ruta, ['id_estudiante' => $estudiante->getKey()])->assertRedirect(route('aula.secciones.show', $seccion));
    $this->post($ruta, ['id_estudiante' => $estudiante->getKey()])->assertSessionHasErrors('id_estudiante');

    $this->assertDatabaseHas('seccion_estudiante', ['id_seccion' => $seccion->getKey(), 'id_usuario' => $estudiante->getKey()]);
    $this->assertDatabaseCount('seccion_estudiante', 1);
});
