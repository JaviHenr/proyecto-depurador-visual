<?php

namespace App\Models\Aula;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Codigo extends Model
{
    protected $table = 'codigo';
    protected $primaryKey = 'id_codigo';
    public $timestamps = false;
    protected $fillable = ['nombre_codigo', 'nombre_archivo', 'formato', 'contenido_codigo'];
    protected $casts = [
        'id_codigo' => 'integer', 'id_usuario' => 'integer', 'id_actividad' => 'integer',
        'fecha_creacion_codigo' => 'date:Y-m-d',
    ];

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class, 'id_actividad', 'id_actividad');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(PerfilUsuario::class, 'id_usuario', config('aula.clave_usuarios', 'id_usuario'));
    }

    /**
     * Códigos entregados por estudiantes en actividades de una sección del profesor.
     * La misma sección debe contener tanto al estudiante como a la actividad.
     */
    public function scopeDeEstudiantesVisiblesPara(Builder $query, Authenticatable $profesor): Builder
    {
        if ($profesor->rol !== 'profesor') {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('codigo.id_usuario', '<>', $profesor->getAuthIdentifier())
            ->whereExists(function ($vinculos) use ($profesor) {
                $vinculos->selectRaw('1')
                    ->from('seccion_actividad as sa')
                    ->join('seccion as s', 's.id_seccion', '=', 'sa.id_seccion')
                    ->join('seccion_estudiante as se', 'se.id_seccion', '=', 's.id_seccion')
                    ->join('usuario as estudiante', 'estudiante.id_usuario', '=', 'se.id_usuario')
                    ->whereColumn('sa.id_actividad', 'codigo.id_actividad')
                    ->whereColumn('se.id_usuario', 'codigo.id_usuario')
                    ->where('s.id_usuario', $profesor->getAuthIdentifier())
                    ->where('estudiante.rol', 'estudiante');
            });
    }

    public function visibleParaProfesor(Authenticatable $profesor): bool
    {
        return self::query()->whereKey($this->getKey())
            ->deEstudiantesVisiblesPara($profesor)->exists();
    }

    // Token de concurrencia calculado. NO es una columna ni un historial de versiones.
    public function huella(): string
    {
        return hash('sha256', json_encode($this->only([
            'id_codigo', 'id_usuario', 'id_actividad', 'nombre_codigo',
            'nombre_archivo', 'formato', 'contenido_codigo', 'fecha_creacion_codigo',
        ]), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
