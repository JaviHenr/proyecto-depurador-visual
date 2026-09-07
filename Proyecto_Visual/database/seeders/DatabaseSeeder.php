<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Siembra o puebla la base de datos de la aplicación.
     */
    public function run(): void
    {
        // Usuario Profesor
        User::updateOrCreate(
            ['email' => 'profesor@depurador.test'],
            [
                'name' => 'Profesor',
                'password' => Hash::make('profesor123'),
                'role' => 'profesor',
            ]
        );

        // Usuario Estudiante
        User::updateOrCreate(
            ['email' => 'estudiante@depurador.test'],
            [
                'name' => 'Estudiante',
                'password' => Hash::make('estudiante123'),
                'role' => 'estudiante',
            ]
        );
    }
}
