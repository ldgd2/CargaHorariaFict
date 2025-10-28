<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Models\Usuario;
use App\Models\Rol;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Variables por .env (opcional)
        $email    = env('ADMIN_EMAIL', 'admin@sistema.test');
        $password = env('ADMIN_PASSWORD', 'Admin123!');
        $nombre   = env('ADMIN_NAME', 'Admin');
        $apellido = env('ADMIN_LASTNAME', 'Principal');

        DB::transaction(function () use ($email, $password, $nombre, $apellido) {
            // Asegurar el rol "Administrador" usando nombre_rol
            $rolAdmin = Rol::firstOrCreate(
                ['nombre_rol' => 'Administrador'],
                ['descripcion' => 'Rol administrador del sistema', 'habilitado' => true]
            );

            // Crear/actualizar el usuario admin (idempotente por email)
            $admin = Usuario::updateOrCreate(
                ['email' => $email],
                [
                    'nombre'          => $nombre,
                    'apellido'        => $apellido,
                    'contrasena_hash' => Hash::make($password),
                    'telefono'        => null,
                    'direccion'       => null,
                    'activo'          => true,
                    // tu tabla tiene DEFAULT CURRENT_TIMESTAMP, pero si quieres setear:
                    'fecha_creacion'  => Carbon::now(),
                ]
            );

            // Asignar el rol en la tabla pivote usuario_rol (id_usuario, id_rol)
            $admin->roles()->syncWithoutDetaching([$rolAdmin->id_rol]);

            $this->command?->info("✓ Admin listo: {$email} (rol: Administrador)");
        });
    }
}
