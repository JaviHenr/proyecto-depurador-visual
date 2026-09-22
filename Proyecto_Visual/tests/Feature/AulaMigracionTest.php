<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{DB, Schema};
use RuntimeException;
use Tests\TestCase;

// SOLO una base PostgreSQL de pruebas, con el esquema nuevo y sin datos reales.
// No utilizar RefreshDatabase: tus tablas iniciales no las crea este paquete.
class AulaMigracionTest extends TestCase
{
    use DatabaseTransactions;

    private function migracion(): Migration
    {
        return require database_path('migrations/2026_09_21_000001_permitir_varias_actividades_por_seccion.php');
    }

    private function actividad(string $nombre): int
    {
        return DB::table('actividad')->insertGetId([
            'nombre_actividad' => $nombre, 'descripcion_actividad' => 'Conservar este texto',
            'instrucciones' => 'Prueba de migración', 'fecha_creacion_actividad' => '2026-09-01 12:30:00',
        ], 'id_actividad');
    }

    private function seccion(): int
    {
        return DB::table('seccion')->insertGetId(['nombre_seccion' => 'Prueba', 'id_usuario' => null], 'id_seccion');
    }

    public function test_migracion_conserva_vinculos_compartidos_actividades_y_codigos(): void
    {
        $a = $this->actividad('Compartida');
        $huerfana = $this->actividad('Existente sin sección');
        $s1 = $this->seccion();
        $s2 = $this->seccion();
        $s3 = $this->seccion();
        DB::table('seccion_actividad')->insert([
            ['id_seccion' => $s1, 'id_actividad' => $a],
            ['id_seccion' => $s2, 'id_actividad' => $a],
        ]);
        $codigo = DB::table('codigo')->insertGetId([
            'id_actividad' => $a, 'nombre_codigo' => 'Preservado', 'contenido_codigo' => "  int x = 1;\n",
            'nombre_archivo' => 'Main.java', 'formato' => 'java', 'fecha_creacion_codigo' => '2026-09-01',
        ], 'id_codigo');
        $datosActividad = (array) DB::table('actividad')->where('id_actividad', $a)->first();
        $datosCodigo = (array) DB::table('codigo')->where('id_codigo', $codigo)->first();
        $migracion = $this->migracion();
        // Recrear el esquema de partida y ejecutar la migración real.
        $migracion->down();
        $this->assertTrue(Schema::hasColumn('seccion', 'id_actividad'));
        $this->assertDatabaseHas('seccion', ['id_seccion' => $s1, 'id_actividad' => $a]);
        $this->assertDatabaseHas('seccion', ['id_seccion' => $s2, 'id_actividad' => $a]);
        $migracion->up();
        $this->assertFalse(Schema::hasColumn('seccion', 'id_actividad'));
        $this->assertDatabaseHas('seccion_actividad', ['id_seccion' => $s1, 'id_actividad' => $a]);
        $this->assertDatabaseHas('seccion_actividad', ['id_seccion' => $s2, 'id_actividad' => $a]);
        $this->assertSame(0, DB::table('seccion_actividad')->where('id_seccion', $s3)->count());
        $this->assertDatabaseHas('actividad', ['id_actividad' => $huerfana]);
        $this->assertSame($datosActividad, (array) DB::table('actividad')->where('id_actividad', $a)->first());
        $this->assertSame($datosCodigo, (array) DB::table('codigo')->where('id_codigo', $codigo)->first());
    }

    public function test_no_revierte_si_hay_varias_actividades_en_una_seccion(): void
    {
        $s = $this->seccion();
        $a1 = $this->actividad('Primera');
        $a2 = $this->actividad('Segunda');
        DB::table('seccion_actividad')->insert([
            ['id_seccion' => $s, 'id_actividad' => $a1],
            ['id_seccion' => $s, 'id_actividad' => $a2],
        ]);
        $rechazada = false;
        try {
            $this->migracion()->down();
        } catch (RuntimeException $error) {
            $rechazada = true;
            $this->assertStringContainsString('varias actividades', $error->getMessage());
        }
        $this->assertTrue($rechazada);
        $this->assertFalse(Schema::hasColumn('seccion', 'id_actividad'));
        $this->assertSame(2, DB::table('seccion_actividad')->where('id_seccion', $s)->count());
    }

    public function test_error_de_dependencia_revierte_toda_la_migracion(): void
    {
        $migracion = $this->migracion();
        $migracion->down();
        DB::statement('CREATE VIEW aula_prueba_dependencia AS SELECT id_seccion, id_actividad FROM seccion');
        $rechazada = false;
        try {
            $migracion->up();
        } catch (QueryException $error) {
            $rechazada = true;
            $this->assertSame('2BP01', (string) $error->getCode());
        }
        $this->assertTrue($rechazada);
        $this->assertTrue(Schema::hasColumn('seccion', 'id_actividad'));
        $this->assertFalse(Schema::hasTable('seccion_actividad'));
        // La transacción de la prueba retirará también la vista.
    }
}
