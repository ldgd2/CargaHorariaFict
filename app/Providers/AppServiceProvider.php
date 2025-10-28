<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Usuario;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bypass global: si es Administrador, autoriza TODO
        Gate::before(function (Usuario $user, string $ability = null) {
            return $user->roles()->where('nombre_rol', 'Administrador')->exists() ? true : null;
        });

        // Habilidades específicas (por si las usas en otras pantallas)
        Gate::define('gestionar_usuarios', fn (Usuario $u) =>
            $u->roles()->where('nombre_rol', 'Administrador')->exists()
        );

        Gate::define('asignar_roles', fn (Usuario $u) =>
            $u->roles()->where('nombre_rol', 'Administrador')->exists()
        );
    }
}
