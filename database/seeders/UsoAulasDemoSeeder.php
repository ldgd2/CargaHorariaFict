<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsoAulasDemoSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * 1) Períodos académicos de prueba
         *    OJO: tu tabla NO tiene created_at/updated_at.
         */

        $periodo2025Id = DB::table('periodo_academico')->insertGetId([
            'nombre'            => '2025-TEST-1',
            'fecha_inicio'      => '2025-02-24',
            'fecha_fin'         => '2025-07-10',
            'activo'            => true,
            'estado_publicacion'=> 'borrador',   // pasa el CHECK
        ], 'id_periodo'); // <<< clave primaria real

        $periodo2026Id = DB::table('periodo_academico')->insertGetId([
            'nombre'            => '2026-TEST-1',
            'fecha_inicio'      => '2026-02-23',
            'fecha_fin'         => '2026-07-09',
            'activo'            => false,
            'estado_publicacion'=> 'publicado',  // publicado => activo = false
        ], 'id_periodo');

        /*
         * 2) Aulas de prueba
         */

        $labA101Id = DB::table('aula')->insertGetId([
            'nombre_aula' => 'LAB A-101',
            'capacidad'   => 40,
            'tipo_aula'   => 'Laboratorio',
            'ubicacion'   => 'Bloque A, Piso 1',
            'habilitado'  => true,
        ], 'id_aula'); // <<< pk real

        $teoB201Id = DB::table('aula')->insertGetId([
            'nombre_aula' => 'TEO B-201',
            'capacidad'   => 30,
            'tipo_aula'   => 'Convencional',
            'ubicacion'   => 'Bloque B, Piso 2',
            'habilitado'  => true,
        ], 'id_aula');

        $audC001Id = DB::table('aula')->insertGetId([
            'nombre_aula' => 'AUD C-001',
            'capacidad'   => 80,
            'tipo_aula'   => 'Auditorio',
            'ubicacion'   => 'Bloque C, Planta baja',
            'habilitado'  => true,
        ], 'id_aula');

        /*
         * 3) Bloqueos por distintos motivos (para probar "aulas ocupadas por X motivo")
         *    Tu migración usa DATE, así que metemos solo 'YYYY-MM-DD'.
         *    registrado_por = null para no depender de la tabla usuario.
         */

        DB::table('bloqueo_aula')->insert([
            [
                'id_aula'       => $labA101Id,
                'fecha_inicio'  => '2025-03-01',
                'fecha_fin'     => '2025-03-01',
                'motivo'        => 'MANTENIMIENTO',
                'registrado_por'=> null,
            ],
            [
                'id_aula'       => $labA101Id,
                'fecha_inicio'  => '2025-03-15',
                'fecha_fin'     => '2025-03-16',
                'motivo'        => 'EVENTO INSTITUCIONAL',
                'registrado_por'=> null,
            ],
            [
                'id_aula'       => $teoB201Id,
                'fecha_inicio'  => '2025-04-10',
                'fecha_fin'     => '2025-04-10',
                'motivo'        => 'FUMIGACION',
                'registrado_por'=> null,
            ],
            [
                'id_aula'       => $audC001Id,
                'fecha_inicio'  => '2025-05-05',
                'fecha_fin'     => '2025-05-05',
                'motivo'        => 'EXAMEN EXTRAORDINARIO',
                'registrado_por'=> null,
            ],
            [
                'id_aula'       => $audC001Id,
                'fecha_inicio'  => '2025-06-01',
                'fecha_fin'     => '2025-06-07',
                'motivo'        => 'REMODELACION',
                'registrado_por'=> null,
            ],
        ]);
    }
}
