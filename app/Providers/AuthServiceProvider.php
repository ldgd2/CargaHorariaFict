<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Usuario;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        
        Gate::define('gestionar_usuarios', function (Usuario $user) {
            return $user->roles()->where('nombre_rol', 'Administrador')->exists();
        });

        
        Gate::define('asignar_roles', function (Usuario $user) {
            return $user->roles()->whereIn('nombre_rol', ['Administrador'])->exists();
        });
    }
}
