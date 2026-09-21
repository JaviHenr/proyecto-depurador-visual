<?php

namespace App\Models\Aula;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};

class Actividad extends Model
{
    protected $table = 'actividad';
    protected $primaryKey = 'id_actividad';
    public $timestamps = false;
    protected $fillable = ['nombre_actividad', 'descripcion_actividad', 'instrucciones'];
    protected $casts = [
        'id_actividad' => 'integer',
        'fecha_creacion_actividad' => 'datetime:Y-m-d H:i:s',
    ];

    public function secciones(): BelongsToMany
    {
        return $this->belongsToMany(Seccion::class, 'seccion_actividad',
            'id_actividad', 'id_seccion', 'id_actividad', 'id_seccion');
    }

    public function codigos(): HasMany
    {
        return $this->hasMany(Codigo::class, 'id_actividad', 'id_actividad');
    }

    public function scopeVisiblesPara(Builder $query, Authenticatable $usuario): Builder
    {
        return $query->whereHas('secciones', fn (Builder $secciones) => $secciones->visiblesPara($usuario));
    }
}
