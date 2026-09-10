<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nombre_usuario
 * @property string $email
 * @property string $rol
 * @property Carbon|null $email_verified_at
 * @property string $contrasena
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nombre_usuario', 'email', 'contrasena', 'rol'])]
#[Hidden(['contrasena', 'remember_token'])]
class Usuario extends Authenticatable
{

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';

    // Si tu tabla no tiene created_at ni updated_at:
    public $timestamps = false;

    // Conserva tus demás propiedades y métodos.

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Obtiene los atributos que deben ser casteados.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'contrasena' => 'hashed',
        ];
    }

    /**
     * Verifica si el usuario tiene rol de profesor.
     */
    public function isProfesor(): bool
    {
        return $this->rol === 'profesor';
    }

    /**
     * Verifica si el usuario tiene rol de estudiante.
     */
    public function isEstudiante(): bool
    {
        return $this->rol === 'estudiante';
    }

    protected $hidden = [
        'contrasena',
    ];

    public function getAuthPasswordName()
    {
        return 'contrasena';
    }
}
