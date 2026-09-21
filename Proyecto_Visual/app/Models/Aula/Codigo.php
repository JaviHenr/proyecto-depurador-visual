<?php

namespace App\Models\Aula;

use Illuminate\Database\Eloquent\Model;
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

    // Token de concurrencia calculado. NO es una columna ni un historial de versiones.
    public function huella(): string
    {
        return hash('sha256', json_encode($this->only([
            'id_codigo', 'id_usuario', 'id_actividad', 'nombre_codigo',
            'nombre_archivo', 'formato', 'contenido_codigo', 'fecha_creacion_codigo',
        ]), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
