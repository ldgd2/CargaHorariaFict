<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route; // <- para comprobar rutas

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $r)
    {
        $cred = $r->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        // Si manejas "activo" en la tabla usuario:
        if (Auth::attempt(
            ['email' => $cred['email'], 'password' => $cred['password'], 'activo' => 1],
            $r->boolean('remember')
        )) {
            $r->session()->regenerate();

            $user  = Auth::user();
            // Trae los nombres de rol en minúsculas para evitar problemas de mayúsculas/minúsculas
            $roles = $user->roles()->pluck('nombre_rol')
                        ->map(fn ($n) => mb_strtolower($n))->toArray();

            // Prioridad de redirección (admin primero si el usuario tiene varios roles)
            $roleRoutes = [
                'administrador' => 'admin.dashboard',
                'coordinador'   => 'coordinador.dashboard',
                'docente'       => 'docente.dashboard',
                'estudiante'    => 'estudiante.dashboard',
            ];

            foreach ($roleRoutes as $needle => $routeName) {
                if (in_array($needle, $roles, true)) {
                    // Respeta intended si el usuario venía de una URL protegida
                    $destino = Route::has($routeName) ? route($routeName) : '/';
                    return redirect()->intended($destino);
                }
            }

            // Fallback si no tiene ningún rol mapeado o las rutas no existen
            $fallback = Route::has('periodos.index')
                        ? route('periodos.index')
                        : (Route::has('admin.dashboard') ? route('admin.dashboard') : '/');
            return redirect()->intended($fallback);
        }

        return back()
            ->withErrors(['email' => 'Credenciales inválidas'])
            ->onlyInput('email');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }
}
