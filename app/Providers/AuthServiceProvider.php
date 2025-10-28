<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Usuario;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Puede entrar a gestionar usuarios
        Gate::define('gestionar_usuarios', function (Usuario $user) {
            return $user->roles()->where('nombre_rol', 'Administrador')->exists();
        });

        // Puede asignar roles (usa lo mismo o ajusta a tu política)
        Gate::define('asignar_roles', function (Usuario $user) {
            return $user->roles()->whereIn('nombre_rol', ['Administrador'])->exists();
        });
    }
}
