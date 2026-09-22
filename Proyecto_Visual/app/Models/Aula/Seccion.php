<?php

namespace App\Models\Aula;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany};

class Seccion extends Model
{
    protected $table = 'seccion';
    protected $primaryKey = 'id_seccion';
    public $timestamps = false;
    protected $fillable = ['nombre_seccion', 'descripcion_seccion'];
    protected $casts = ['id_seccion' => 'integer', 'id_usuario' => 'integer'];

    public function profesor(): BelongsTo
    {
        return $this->belongsTo(PerfilUsuario::class, 'id_usuario', config('aula.clave_usuarios', 'id_usuario'));
    }

    public function actividades(): BelongsToMany
    {
        return $this->belongsToMany(Actividad::class, 'seccion_actividad',
            'id_seccion', 'id_actividad', 'id_seccion', 'id_actividad');
    }

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(PerfilUsuario::class, 'seccion_estudiante',
            'id_seccion', 'id_usuario', 'id_seccion', config('aula.clave_usuarios', 'id_usuario'))
            ->withPivot('fecha_inscripcion');
    }

    public function scopeVisiblesPara(Builder $query, Authenticatable $usuario): Builder
    {
        if ($usuario->rol === 'profesor') {
            return $query->where('seccion.id_usuario', $usuario->getAuthIdentifier());
        }

        if ($usuario->rol === 'estudiante') {
            return $query->whereExists(function ($matriculas) use ($usuario) {
                $matriculas->selectRaw('1')->from('seccion_estudiante')
                    ->whereColumn('seccion_estudiante.id_seccion', 'seccion.id_seccion')
                    ->where('seccion_estudiante.id_usuario', $usuario->getAuthIdentifier());
            });
        }

        return $query->whereRaw('1 = 0');
    }
}
