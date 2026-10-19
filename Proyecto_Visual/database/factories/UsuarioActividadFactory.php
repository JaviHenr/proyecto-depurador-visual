<?php

namespace Database\Factories;

use App\Models\Usuario;

/** Reutiliza los estados existentes fijando explícitamente el modelo Usuario. */
class UsuarioActividadFactory extends UserFactory
{
    protected $model = Usuario::class;
}
