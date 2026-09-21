<?php

namespace App\Models\Aula;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Solo para leer el nombre del profesor; no reemplaza tu modelo de autenticación.
class PerfilUsuario extends Model
{
    public $timestamps = false;
    protected $guarded = ['*'];

    public function getTable(): string
    {
        return config('aula.tabla_usuarios', 'usuario');
    }

    public function getKeyName(): string
    {
        return config('aula.clave_usuarios', 'id_usuario');
    }

    public function seccionesInscritas(): BelongsToMany
    {
        return $this->belongsToMany(Seccion::class, 'seccion_estudiante',
            'id_usuario', 'id_seccion', $this->getKeyName(), 'id_seccion')
            ->withPivot('fecha_inscripcion');
    }
}
