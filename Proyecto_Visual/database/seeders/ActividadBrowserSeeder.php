<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Support\EsquemaActividad;

class ActividadBrowserSeeder extends Seeder
{
    public function run(): void
    {
        $conexion = DB::connection();
        $archivo = str_replace('\\', '/', $conexion->getDatabaseName());
        if (! app()->environment('testing') || $conexion->getDriverName() !== 'sqlite'
            || ! str_ends_with($archivo, '/actividad-testing.sqlite')) {
            throw new RuntimeException('Usa --env=testing y database/actividad-testing.sqlite.');
        }
        if (! Schema::hasTable('usuario')) {
            EsquemaActividad::crear();
        }

        Usuario::updateOrCreate(['email' => 'e2e.actividad@example.test'], [
            'nombre_usuario' => 'Estudiante actividad',
            'rol' => 'estudiante',
            'email_verified_at' => now(),
            'contrasena' => Hash::make('SoloParaPruebas123!'),
        ]);
    }
}
