<?php

namespace App\Policies\Aula;

use App\Models\Aula\Seccion;
use Illuminate\Contracts\Auth\Authenticatable;

class SeccionPolicy
{
    public function viewAny(Authenticatable $usuario): bool
    {
        return in_array($usuario->rol, ['profesor', 'estudiante'], true);
    }

    public function create(Authenticatable $usuario): bool
    {
        return $usuario->rol === 'profesor';
    }

    public function view(Authenticatable $usuario, Seccion $seccion): bool
    {
        if ($this->esResponsable($usuario, $seccion)) {
            return true;
        }

        return $usuario->rol === 'estudiante' && $seccion->estudiantes()
            ->wherePivot('id_usuario', $usuario->getAuthIdentifier())->exists();
    }

    public function update(Authenticatable $usuario, Seccion $seccion): bool
    {
        return $this->esResponsable($usuario, $seccion);
    }

    public function delete(Authenticatable $usuario, Seccion $seccion): bool
    {
        return $this->esResponsable($usuario, $seccion);
    }

    private function esResponsable(Authenticatable $usuario, Seccion $seccion): bool
    {
        return $usuario->rol === 'profesor' && $seccion->id_usuario !== null
            && (string) $seccion->id_usuario === (string) $usuario->getAuthIdentifier();
    }
}
