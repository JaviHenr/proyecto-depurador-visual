<?php

namespace App\Models;

use App\Models\Aula\{Codigo, Seccion};
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'nombre_usuario',
        'email',
        'rol',
        'contrasena',
    ];

    protected $hidden = [
        'contrasena',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'id_usuario' => 'integer',
            'email_verified_at' => 'datetime',
            'contrasena' => 'hashed',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->contrasena;
    }

    public function seccionesDirigidas(): HasMany
    {
        return $this->hasMany(Seccion::class, 'id_usuario', 'id_usuario');
    }

    public function seccionesInscritas(): BelongsToMany
    {
        return $this->belongsToMany(Seccion::class, 'seccion_estudiante',
            'id_usuario', 'id_seccion', 'id_usuario', 'id_seccion')
            ->withPivot('fecha_inscripcion');
    }

    public function codigos(): HasMany
    {
        return $this->hasMany(Codigo::class, 'id_usuario', 'id_usuario');
    }
}
