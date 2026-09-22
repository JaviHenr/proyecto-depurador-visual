<?php

namespace Tests\Feature;

use App\Models\Aula\{Actividad, Codigo, Seccion};
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// Requiere una base PostgreSQL de PRUEBAS creada con Esquema_Depurador_Visual.sql.
// No ejecuta migraciones ni crea tablas. Cada prueba revierte sus operaciones.
class AulaCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function usuario(string $rol): Authenticatable
    {
        $guard = config('auth.defaults.guard');
        $provider = config("auth.guards.$guard.provider");
        $modelo = config("auth.providers.$provider.model");
        $usuario = new $modelo;
        // Ajusta SOLO este helper si usuario exige otros campos obligatorios.
        $usuario->forceFill([
            'nombre_usuario' => ucfirst($rol).' Prueba',
            'email' => Str::uuid().'@example.test',
            'rol' => $rol,
            'contrasena' => Hash::make('SoloParaPruebas123!'),
        ])->save();
        return $usuario;
    }

    private function seccion(Authenticatable $profesor): Seccion
    {
        $seccion = new Seccion(['nombre_seccion' => 'Programación I', 'descripcion_seccion' => 'Prueba']);
        $seccion->id_usuario = $profesor->getAuthIdentifier();
        $seccion->save();
        return $seccion;
    }

    private function actividad(Seccion $seccion): Actividad
    {
        $actividad = new Actividad($this->datosActividad());
        $actividad->fecha_creacion_actividad = now();
        $actividad->save();
        $seccion->actividades()->attach($actividad->getKey());
        return $actividad;
    }

    private function datosActividad(): array
    {
        return ['nombre_actividad' => 'Ciclo for', 'descripcion_actividad' => 'Recorre el arreglo.', 'instrucciones' => 'Observa cada índice.'];
    }

    private function datosCodigo(?int $actividadId = null): array
    {
        return [
            'nombre_codigo' => 'Mi programa', 'nombre_archivo' => 'Main.java', 'formato' => 'java',
            'contenido_codigo' => "  public class Main {\n}\n\n", 'id_actividad' => $actividadId,
        ];
    }

    public function test_seccion_usa_la_clave_real_y_el_profesor_de_la_sesion(): void
    {
        $this->get('/secciones')->assertRedirect('/login');
        $estudiante = $this->usuario('estudiante');
        $datos = ['nombre_seccion' => 'Nueva sección', 'descripcion_seccion' => 'Descripción'];
        $this->actingAs($estudiante)->post('/secciones', $datos)->assertForbidden();
        $profesor = $this->usuario('profesor');
        $this->actingAs($profesor)->post('/secciones', $datos + [
            'id_usuario' => $estudiante->getAuthIdentifier(), 'id_actividad' => 999999,
        ])->assertRedirect();
        $seccion = Seccion::where('id_usuario', $profesor->getAuthIdentifier())->firstOrFail();
        $this->assertArrayNotHasKey('id_actividad', $seccion->getAttributes());
        $this->assertSame(0, $seccion->actividades()->count());
        $this->assertGreaterThan(0, $seccion->id_seccion);
        $this->get('/secciones')->assertInertia(fn (Assert $page) => $page
            ->component('Aula/Secciones/Index')->where('secciones.data.0.id_seccion', $seccion->id_seccion));
        $this->put('/secciones/'.$seccion->id_seccion, array_replace($datos, ['nombre_seccion' => 'Editada']))->assertRedirect();
        $this->assertDatabaseHas('seccion', ['id_seccion' => $seccion->id_seccion, 'nombre_seccion' => 'Editada']);
        $this->delete('/secciones/'.$seccion->id_seccion)->assertRedirect('/secciones');
        $this->assertDatabaseMissing('seccion', ['id_seccion' => $seccion->id_seccion]);
    }

    public function test_seccion_admite_dos_actividades_y_se_editan_y_eliminan_independientemente(): void
    {
        $profesor = $this->usuario('profesor');
        $seccion = $this->seccion($profesor);
        $base = '/secciones/'.$seccion->id_seccion.'/actividades';
        $this->actingAs($profesor)->post($base, $this->datosActividad())->assertRedirect();
        $this->post($base, array_replace($this->datosActividad(), ['nombre_actividad' => 'Ciclo while']))->assertRedirect();
        $actividades = $seccion->actividades()->orderBy('actividad.id_actividad')->get();
        $this->assertCount(2, $actividades);
        [$primera, $segunda] = $actividades->all();
        $fecha = $primera->fecha_creacion_actividad->format('Y-m-d H:i:s');
        $this->get('/secciones/'.$seccion->id_seccion)->assertInertia(fn (Assert $page) => $page
            ->component('Aula/Secciones/Show')->has('actividades.data', 2)
            ->where('seccion.actividades_count', 2));
        $this->get('/secciones/'.$seccion->id_seccion.'?q=while')->assertInertia(fn (Assert $page) => $page
            ->has('actividades.data', 1)->where('actividades.data.0.id_actividad', $segunda->id_actividad));
        $this->put($base.'/'.$primera->id_actividad, array_replace($this->datosActividad(), [
            'nombre_actividad' => 'Ciclo for editado', 'fecha_creacion_actividad' => '2000-01-01 00:00:00',
        ]))->assertRedirect();
        $this->assertSame('Ciclo for editado', $primera->fresh()->nombre_actividad);
        $this->assertSame('Ciclo while', $segunda->fresh()->nombre_actividad);
        $this->assertSame($fecha, $primera->fresh()->fecha_creacion_actividad->format('Y-m-d H:i:s'));
        $this->delete($base.'/'.$primera->id_actividad)->assertRedirect();
        $this->assertSame(1, $seccion->actividades()->count());
        $this->assertDatabaseHas('seccion_actividad', ['id_seccion' => $seccion->id_seccion, 'id_actividad' => $segunda->id_actividad]);
        $this->assertDatabaseMissing('actividad', ['id_actividad' => $primera->id_actividad]);
    }

    public function test_otro_profesor_no_lee_ni_modifica_la_seccion(): void
    {
        $seccion = $this->seccion($this->usuario('profesor'));
        $actividad = $this->actividad($seccion);
        $otro = $this->usuario('profesor');
        $url = '/secciones/'.$seccion->id_seccion;
        $this->actingAs($otro)->get($url)->assertForbidden();
        $this->put($url, ['nombre_seccion' => 'Intrusión'])->assertForbidden();
        $this->delete($url)->assertForbidden();
        $this->put($url.'/actividades/'.$actividad->id_actividad, $this->datosActividad())->assertForbidden();
        $this->delete($url.'/actividades/'.$actividad->id_actividad)->assertForbidden();
        $this->post('/codigos', $this->datosCodigo($actividad->id_actividad))->assertForbidden();
    }

    public function test_id_de_actividad_no_permite_editar_otra_seccion(): void
    {
        $profesor = $this->usuario('profesor');
        $s1 = $this->seccion($profesor);
        $s2 = $this->seccion($profesor);
        $a1 = $this->actividad($s1);
        $a2 = $this->actividad($s2);
        $url = '/secciones/'.$s1->id_seccion.'/actividades/'.$a2->id_actividad;
        $this->actingAs($profesor)->put($url, $this->datosActividad())->assertNotFound();
        $this->delete($url)->assertNotFound();
        $this->delete($url.'/vinculo')->assertNotFound();
        $this->assertDatabaseHas('seccion_actividad', ['id_seccion' => $s1->id_seccion, 'id_actividad' => $a1->id_actividad]);
        $this->assertDatabaseHas('actividad', ['id_actividad' => $a2->id_actividad]);
    }

    public function test_codigo_es_privado_y_detecta_edicion_desactualizada_sin_columna_version(): void
    {
        $autor = $this->usuario('estudiante');
        $otro = $this->usuario('estudiante');
        $datos = $this->datosCodigo();
        $this->actingAs($autor)->post('/codigos', $datos + ['id_usuario' => $otro->getAuthIdentifier()])->assertRedirect();
        $codigo = Codigo::where('id_usuario', $autor->getAuthIdentifier())->firstOrFail();
        $this->assertSame($datos['contenido_codigo'], $codigo->contenido_codigo);
        $this->assertSame(now()->toDateString(), $codigo->fecha_creacion_codigo->toDateString());
        $url = '/codigos/'.$codigo->id_codigo;
        $huella = $codigo->huella();
        $this->get($url.'/editar')->assertRedirect('/depurador/'.$codigo->id_codigo);
        $this->get('/depurador/'.$codigo->id_codigo)->assertInertia(fn (Assert $page) => $page
            ->component('DepuradorVisual')->where('codigo.huella', $huella)
            ->where('permisosCodigo.editar', true));
        $nuevos = array_replace($datos, ['contenido_codigo' => 'public class Editado {}', 'huella' => $huella]);
        $this->put($url, $nuevos)->assertRedirect();
        $this->put($url, $datos + ['huella' => $huella])->assertSessionHasErrors('huella');
        $this->assertSame('public class Editado {}', $codigo->fresh()->contenido_codigo);
        $this->actingAs($otro)->get($url.'/editar')->assertForbidden();
        $this->get('/depurador/'.$codigo->id_codigo)->assertForbidden();
        $this->put($url, $nuevos)->assertForbidden();
        $this->delete($url)->assertForbidden();
        $this->get('/codigos')->assertInertia(fn (Assert $page) => $page->has('codigos.data', 0));
        $this->actingAs($autor)->delete($url)->assertRedirect('/codigos');
        $this->assertDatabaseMissing('codigo', ['id_codigo' => $codigo->id_codigo]);
    }

    public function test_borrado_respetando_codigos_y_claves_foraneas(): void
    {
        $profesor = $this->usuario('profesor');
        $seccion = $this->seccion($profesor);
        $actividad = $this->actividad($seccion);
        $this->actingAs($profesor)->post('/codigos', $this->datosCodigo($actividad->id_actividad))->assertRedirect();
        $codigo = Codigo::where('id_usuario', $profesor->getAuthIdentifier())->firstOrFail();
        $url = '/secciones/'.$seccion->id_seccion;
        $this->delete($url)->assertSessionHasErrors('eliminar');
        $this->delete($url.'/actividades/'.$actividad->id_actividad)->assertSessionHasErrors('eliminar');
        $this->assertDatabaseHas('seccion_actividad', ['id_seccion' => $seccion->id_seccion, 'id_actividad' => $actividad->id_actividad]);
        $this->put('/codigos/'.$codigo->id_codigo, $this->datosCodigo() + ['huella' => $codigo->huella()])->assertRedirect();
        $this->assertNull($codigo->fresh()->id_actividad);
        $this->delete($url.'/actividades/'.$actividad->id_actividad)->assertRedirect();
        $this->delete($url)->assertRedirect('/secciones');
        $this->assertDatabaseHas('codigo', ['id_codigo' => $codigo->id_codigo]);
    }

    public function test_actividad_compartida_es_lectura_y_se_puede_retirar_su_vinculo(): void
    {
        $profesor = $this->usuario('profesor');
        $s1 = $this->seccion($profesor);
        $s2 = $this->seccion($this->usuario('profesor'));
        $actividad = $this->actividad($s1);
        $s2->actividades()->attach($actividad->getKey());
        $url = '/secciones/'.$s1->id_seccion;
        $this->actingAs($profesor)->get($url)->assertInertia(fn (Assert $page) => $page
            ->where('actividades.data.0.permisos.editar', false)->where('actividades.data.0.permisos.desvincular', true));
        $this->put($url.'/actividades/'.$actividad->id_actividad, $this->datosActividad())->assertForbidden();
        $this->delete($url.'/actividades/'.$actividad->id_actividad)->assertForbidden();
        $this->delete($url.'/actividades/'.$actividad->id_actividad.'/vinculo')->assertRedirect();
        $this->assertSame(0, $s1->actividades()->count());
        $this->assertDatabaseHas('seccion_actividad', ['id_seccion' => $s2->id_seccion, 'id_actividad' => $actividad->id_actividad]);
        $this->assertDatabaseHas('actividad', ['id_actividad' => $actividad->id_actividad]);
    }

    public function test_matricula_habilita_acceso_y_el_profesor_la_administra(): void
    {
        $profesor = $this->usuario('profesor');
        $seccion = $this->seccion($profesor);
        $actividad = $this->actividad($seccion);
        $estudiante = $this->usuario('estudiante');
        $noInscrito = $this->usuario('estudiante');

        // Sin matrícula no puede ver la sección ni asociar un código nuevo.
        $this->actingAs($estudiante)->get('/secciones')->assertInertia(fn (Assert $page) => $page->has('secciones.data', 0));
        $this->get('/secciones/'.$seccion->id_seccion)->assertForbidden();
        $this->get('/codigos/nuevo?actividad='.$actividad->id_actividad)->assertForbidden();
        $this->post('/codigos', $this->datosCodigo($actividad->id_actividad))->assertForbidden();

        // Solo el profesor responsable agrega estudiantes y solo por una cuenta estudiante.
        $ruta = '/secciones/'.$seccion->id_seccion.'/estudiantes';
        $this->actingAs($profesor)->post($ruta, ['email_estudiante' => $estudiante->email])->assertRedirect();
        $this->assertDatabaseHas('seccion_estudiante', [
            'id_seccion' => $seccion->id_seccion,
            'id_usuario' => $estudiante->getAuthIdentifier(),
        ]);
        $this->post($ruta, ['email_estudiante' => $estudiante->email])->assertSessionHasErrors('email_estudiante');
        $this->post($ruta, ['email_estudiante' => $profesor->email])->assertSessionHasErrors('email_estudiante');

        // El estudiante inscrito consulta las actividades y crea su propio código.
        $this->actingAs($estudiante)->get('/secciones')->assertInertia(fn (Assert $page) => $page
            ->has('secciones.data', 1)
            ->where('secciones.data.0.estudiantes_count', 1));
        $this->get('/secciones/'.$seccion->id_seccion)->assertInertia(fn (Assert $page) => $page
            ->component('Aula/Secciones/Show')
            ->where('estudiantes', null)
            ->where('permisos.gestionar', false));
        $this->post('/codigos', $this->datosCodigo($actividad->id_actividad))->assertRedirect();
        $codigo = Codigo::where('id_usuario', $estudiante->getAuthIdentifier())->firstOrFail();
        $this->post('/codigos', $this->datosCodigo())->assertRedirect();
        $this->assertDatabaseHas('codigo', [
            'id_usuario' => $estudiante->getAuthIdentifier(), 'id_actividad' => null,
        ]);

        // El profesor ve la entrega en modo de solo lectura, nunca los códigos personales.
        $this->actingAs($profesor)->get('/codigos?vista=estudiantes')->assertInertia(fn (Assert $page) => $page
            ->component('Aula/Codigos/Index')
            ->has('codigos.data', 1)
            ->where('codigos.data.0.id_codigo', $codigo->id_codigo)
            ->where('codigos.data.0.permisos.editar', false));
        $this->get('/depurador/'.$codigo->id_codigo)->assertInertia(fn (Assert $page) => $page
            ->component('DepuradorVisual')
            ->where('codigo.contenido_codigo', $codigo->contenido_codigo)
            ->where('permisosCodigo.editar', false));
        $this->put('/codigos/'.$codigo->id_codigo, $this->datosCodigo($actividad->id_actividad) + [
            'huella' => $codigo->huella(),
        ])->assertForbidden();
        $this->delete('/codigos/'.$codigo->id_codigo)->assertForbidden();

        // Ver no implica administrar.
        $base = '/secciones/'.$seccion->id_seccion;
        $this->put($base, ['nombre_seccion' => 'No autorizado'])->assertForbidden();
        $this->post($base.'/actividades', $this->datosActividad())->assertForbidden();
        $this->put($base.'/actividades/'.$actividad->id_actividad, $this->datosActividad())->assertForbidden();
        $this->post($ruta, ['email_estudiante' => $noInscrito->email])->assertForbidden();
        $this->delete($ruta.'/'.$estudiante->getAuthIdentifier())->assertForbidden();
        $this->actingAs($noInscrito)->get($base)->assertForbidden();

        // Al retirarlo pierde el acceso futuro, pero su código no se elimina.
        $this->actingAs($profesor)->delete($ruta.'/'.$estudiante->getAuthIdentifier())->assertRedirect();
        $this->assertDatabaseMissing('seccion_estudiante', [
            'id_seccion' => $seccion->id_seccion,
            'id_usuario' => $estudiante->getAuthIdentifier(),
        ]);
        $this->actingAs($estudiante)->get($base)->assertForbidden();
        $this->actingAs($profesor)->get('/depurador/'.$codigo->id_codigo)->assertForbidden();
        $url = '/codigos/'.$codigo->id_codigo;
        $this->actingAs($estudiante)->get($url.'/editar')->assertRedirect('/depurador/'.$codigo->id_codigo);
        $this->get('/depurador/'.$codigo->id_codigo)->assertOk();
        $this->put($url, $this->datosCodigo($actividad->id_actividad) + ['huella' => $codigo->fresh()->huella()])->assertRedirect();
        $this->put($url, $this->datosCodigo() + ['huella' => $codigo->fresh()->huella()])->assertRedirect();
        $this->assertNull($codigo->fresh()->id_actividad);
    }

    public function test_limites_coinciden_con_los_varchar_de_la_base(): void
    {
        $profesor = $this->usuario('profesor');
        $seccion = $this->seccion($profesor);
        $this->actingAs($profesor)->post('/secciones', ['nombre_seccion' => str_repeat('a', 201)])->assertSessionHasErrors('nombre_seccion');
        $this->post('/secciones/'.$seccion->id_seccion.'/actividades', array_replace($this->datosActividad(), [
            'nombre_actividad' => str_repeat('a', 151), 'instrucciones' => str_repeat('a', 201),
        ]))->assertSessionHasErrors(['nombre_actividad', 'instrucciones']);
        $this->post('/codigos', array_replace($this->datosCodigo(), [
            'nombre_codigo' => str_repeat('a', 151), 'nombre_archivo' => str_repeat('a', 201), 'formato' => str_repeat('a', 21),
        ]))->assertSessionHasErrors(['nombre_codigo', 'nombre_archivo', 'formato']);
    }
}
