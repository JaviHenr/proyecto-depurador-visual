<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();

        DB::table('usuario')->upsert([
            [
                'nombre_usuario' => 'Profesor de prueba',
                'email' => 'profesor@depurador.test',
                'rol' => 'profesor',
                'contrasena' => Hash::make('profesor123'),
                'remember_token' => null,
                'email_verified_at' => $ahora,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre_usuario' => 'Estudiante de prueba',
                'email' => 'estudiante@depurador.test',
                'rol' => 'estudiante',
                'contrasena' => Hash::make('estudiante123'),
                'remember_token' => null,
                'email_verified_at' => $ahora,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
        ], [
            'email',
        ], [
            'nombre_usuario',
            'rol',
            'contrasena',
            'email_verified_at',
            'updated_at',
        ]);
    }
}