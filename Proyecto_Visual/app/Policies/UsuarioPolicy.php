<?php

namespace App\Policies;

use App\Models\Usuario;

class UsuarioPolicy
{
    public function viewAny(Usuario $actor): bool
    {
        return $actor->rol === 'profesor';
    }

    public function create(Usuario $actor): bool
    {
        return $actor->rol === 'profesor';
    }

    public function update(Usuario $actor, Usuario $usuario): bool
    {
        return $actor->rol === 'profesor'
            && ($usuario->rol === 'estudiante' || $actor->is($usuario));
    }
}
