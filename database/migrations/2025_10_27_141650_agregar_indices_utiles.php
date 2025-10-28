<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // grupo (id_periodo, id_materia, id_carrera)
        Schema::table('grupo', function (Blueprint $table) {
            $table->index(['id_periodo', 'id_materia', 'id_carrera'], 'idx_grupo_per_mate_car');
        });

        // carga_horaria (id_docente, dia_semana, start_min, end_min)
        Schema::table('carga_horaria', function (Blueprint $table) {
            $table->index(['id_docente', 'dia_semana', 'start_min', 'end_min'], 'idx_carga_docente_dia');
        });

        // carga_horaria (id_aula, dia_semana, start_min, end_min)
        Schema::table('carga_horaria', function (Blueprint $table) {
            $table->index(['id_aula', 'dia_semana', 'start_min', 'end_min'], 'idx_carga_aula_dia');
        });

        // asistencia_sesion (id_carga, fecha_sesion)
        Schema::table('asistencia_sesion', function (Blueprint $table) {
            $table->index(['id_carga', 'fecha_sesion'], 'idx_asistencia_carga_fecha');
        });

        // disponibilidad_docente (id_docente, id_periodo, dia_semana, hora_inicio, hora_fin)
        Schema::table('disponibilidad_docente', function (Blueprint $table) {
            $table->index(
                ['id_docente', 'id_periodo', 'dia_semana', 'hora_inicio', 'hora_fin'],
                'idx_disp_doc_per_dia'
            );
        });

        // bloqueo_aula (id_aula, fecha_inicio, fecha_fin)
        Schema::table('bloqueo_aula', function (Blueprint $table) {
            $table->index(['id_aula', 'fecha_inicio', 'fecha_fin'], 'idx_bloqueo_aula_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('grupo', function (Blueprint $table) {
            $table->dropIndex('idx_grupo_per_mate_car');
        });

        Schema::table('carga_horaria', function (Blueprint $table) {
            $table->dropIndex('idx_carga_docente_dia');
            $table->dropIndex('idx_carga_aula_dia');
        });

        Schema::table('asistencia_sesion', function (Blueprint $table) {
            $table->dropIndex('idx_asistencia_carga_fecha');
        });

        Schema::table('disponibilidad_docente', function (Blueprint $table) {
            $table->dropIndex('idx_disp_doc_per_dia');
        });

        Schema::table('bloqueo_aula', function (Blueprint $table) {
            $table->dropIndex('idx_bloqueo_aula_fecha');
        });
    }
};
