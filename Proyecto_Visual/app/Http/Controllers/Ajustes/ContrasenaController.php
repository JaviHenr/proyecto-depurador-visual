<?php

namespace App\Http\Controllers\Ajustes;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContrasenaController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $estudiante = $request->user();
        abort_unless($estudiante->rol === 'estudiante', 403);

        $datos = $request->validate([
            'current_password' => ['bail', 'required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Escribe tu contraseña actual.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.required' => 'Escribe tu nueva contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La contraseña no puede superar los 72 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.different' => 'La nueva contraseña debe ser distinta de la actual.',
        ]);

        if (strlen($datos['password']) > 72 || str_contains($datos['password'], "\0")) {
            throw ValidationException::withMessages(['password' => 'Usa una contraseña de hasta 72 bytes, sin caracteres nulos.']);
        }

        $estudiante->contrasena = Hash::make($datos['password']);
        $estudiante->setRememberToken(Str::random(60));
        $estudiante->save();
        $request->session()->regenerate();

        return to_route('ajustes');
    }
}
