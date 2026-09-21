<?php

namespace App\Policies\Aula;

use App\Models\Aula\{Actividad, Seccion};
use Illuminate\Contracts\Auth\Authenticatable;

class ActividadPolicy
{
    public function view(Authenticatable $usuario, Actividad $actividad): bool
    {
        return $actividad->secciones()->visiblesPara($usuario)->exists();
    }

    public function create(Authenticatable $usuario, Seccion $seccion): bool
    {
        return (new SeccionPolicy)->update($usuario, $seccion);
    }

    public function update(Authenticatable $usuario, Actividad $actividad): bool
    {
        // Un estudiante puede consultar la actividad, pero nunca administrarla.
        // Sin propietario en actividad, el profesor solo edita una actividad exclusiva
        // de una sección que le pertenece.
        return $usuario->rol === 'profesor'
            && $actividad->secciones()->count() === 1
            && $actividad->secciones()
                ->where('seccion.id_usuario', $usuario->getAuthIdentifier())->exists();
    }

    public function delete(Authenticatable $usuario, Actividad $actividad): bool
    {
        return $this->update($usuario, $actividad);
    }
}
