<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;

class RoolSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Administrador','Coordinador','Docente','Estudiante'] as $nombre) {
            Rol::firstOrCreate(['nombre_rol' => $nombre], ['habilitado' => true]);
        }
    }
}
