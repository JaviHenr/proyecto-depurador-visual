<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * La contraseña actual utilizada por la factoría.
     */
    protected static ?string $password;

    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => 'estudiante',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indica que el usuario tiene rol de profesor.
     */
    public function profesor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'profesor',
        ]);
    }

    /**
     * Indica que el usuario tiene rol de estudiante.
     */
    public function estudiante(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'estudiante',
        ]);
    }

    /**
     * Indica que la dirección de correo del modelo no debe estar verificada.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
