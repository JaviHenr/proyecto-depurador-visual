<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Crea o actualiza los usuarios de prueba.
     */
    public function run(): void
    {
        Usuario::updateOrCreate(
            ['email' => 'profesor@depurador.test'],
            [
                'nombre_usuario' => 'Profesor',
                'contrasena' => Hash::make('profesor123'),
                'rol' => 'profesor',
            ]
        );

        Usuario::updateOrCreate(
            ['email' => 'estudiante@depurador.test'],
            [
                'nombre_usuario' => 'Estudiante',
                'contrasena' => Hash::make('estudiante123'),
                'rol' => 'estudiante',
            ]
        );
    }
}