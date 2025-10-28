<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grupo', function (Blueprint $table) {
            $table->increments('id_grupo');
            $table->integer('id_periodo');
            $table->integer('id_materia');
            $table->integer('id_carrera');
            $table->string('nombre_grupo', 50);
            $table->integer('capacidad_estudiantes');
            $table->string('estado', 20)->default('En Asignacion');

            // FKs
            $table->foreign('id_periodo')->references('id_periodo')->on('periodo_academico')->restrictOnDelete();
            $table->foreign('id_materia')->references('id_materia')->on('materia')->restrictOnDelete();
            $table->foreign('id_carrera')->references('id_carrera')->on('carrera')->restrictOnDelete();

            // UNIQUE compuesto
            $table->unique(['id_periodo', 'id_materia', 'id_carrera', 'nombre_grupo'], 'uq_grupo_periodo_materia_carrera_nombre');
        });

        // CHECK de estado
        DB::statement("
            ALTER TABLE grupo
            ADD CONSTRAINT chk_grupo_estado
            CHECK (estado IN ('En Asignacion','Activo','Cerrado','Incompleto'))
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE grupo DROP CONSTRAINT IF EXISTS chk_grupo_estado");
        Schema::dropIfExists('grupo');
    }
};
