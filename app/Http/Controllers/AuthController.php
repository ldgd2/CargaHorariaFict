<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Si usas "activo" en tabla usuario:
        if (Auth::attempt(['email'=>$cred['email'], 'password'=>$cred['password'], 'activo'=>1])) {
            $r->session()->regenerate();

            $user = Auth::user();
            // Redirigir por rol (simple: si es Admin, al dashboard admin)
            if ($user->roles()->where('nombre_rol','Administrador')->exists()) {
                return redirect()->route('admin.dashboard');
            }

            // Otras rutas por rol (opcional)
            // if ($user->roles()->where('nombre_rol','Coordinador')->exists()) { ... }

            // Fallback
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['email' => 'Credenciales inválidas'])->onlyInput('email');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }
}
