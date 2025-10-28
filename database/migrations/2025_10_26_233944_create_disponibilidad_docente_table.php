<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disponibilidad_docente', function (Blueprint $table) {
            $table->increments('id_disponibilidad');
            $table->integer('id_docente');
            $table->integer('id_periodo');
            $table->integer('dia_semana'); // 1..7
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->text('observaciones')->nullable();
            $table->integer('prioridad')->default(1);

            // FKs
            $table->foreign('id_docente')->references('id_docente')->on('docente')->cascadeOnDelete();
            $table->foreign('id_periodo')->references('id_periodo')->on('periodo_academico')->cascadeOnDelete();

            // UNIQUE
            $table->unique(
                ['id_docente', 'id_periodo', 'dia_semana', 'hora_inicio', 'hora_fin'],
                'uq_disp_doc_per_dia_rango'
            );
        });

        // CHECKs
        DB::statement("
            ALTER TABLE disponibilidad_docente
            ADD CONSTRAINT chk_disp_doc_dia
            CHECK (dia_semana BETWEEN 1 AND 7)
        ");
        DB::statement("
            ALTER TABLE disponibilidad_docente
            ADD CONSTRAINT chk_disp_doc_horas
            CHECK (hora_fin > hora_inicio)
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE disponibilidad_docente DROP CONSTRAINT IF EXISTS chk_disp_doc_horas");
        DB::statement("ALTER TABLE disponibilidad_docente DROP CONSTRAINT IF EXISTS chk_disp_doc_dia");
        Schema::dropIfExists('disponibilidad_docente');
    }
};
