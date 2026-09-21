<?php

namespace App\Policies\Aula;

use App\Models\Aula\Codigo;
use Illuminate\Contracts\Auth\Authenticatable;

class CodigoPolicy
{
    public function viewAny(Authenticatable $usuario): bool
    {
        return in_array($usuario->rol, ['profesor', 'estudiante'], true);
    }

    public function create(Authenticatable $usuario): bool
    {
        return $this->viewAny($usuario);
    }

    public function view(Authenticatable $usuario, Codigo $codigo): bool
    {
        return $this->viewAny($usuario) && $codigo->id_usuario !== null
            && (string) $codigo->id_usuario === (string) $usuario->getAuthIdentifier();
    }

    public function update(Authenticatable $usuario, Codigo $codigo): bool
    {
        return $this->view($usuario, $codigo);
    }

    public function delete(Authenticatable $usuario, Codigo $codigo): bool
    {
        return $this->view($usuario, $codigo);
    }
}
