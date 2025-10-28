<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('carga_horaria', function (Blueprint $table) {
            $table->increments('id_carga');
            $table->integer('id_grupo');
            $table->integer('id_docente');
            $table->integer('id_aula');
            $table->integer('dia_semana'); // 1..7
            $table->time('hora_inicio');
            $table->time('hora_fin');
            // columnas generadas (PostgreSQL)
            $table->integer('start_min')->storedAs("(EXTRACT(HOUR FROM hora_inicio)::int*60 + EXTRACT(MINUTE FROM hora_inicio)::int)");
            $table->integer('end_min')->storedAs("(EXTRACT(HOUR FROM hora_fin)::int*60 + EXTRACT(MINUTE FROM hora_fin)::int)");
            $table->timestamp('fecha_asignacion', 0)->useCurrent();
            $table->string('estado', 20)->default('Vigente');

            // FKs
            $table->foreign('id_grupo')->references('id_grupo')->on('grupo')->cascadeOnDelete();
            $table->foreign('id_docente')->references('id_docente')->on('docente')->restrictOnDelete();
            $table->foreign('id_aula')->references('id_aula')->on('aula')->restrictOnDelete();
        });

        // CHECKs
        DB::statement("
            ALTER TABLE carga_horaria
            ADD CONSTRAINT chk_carga_horaria_dia
            CHECK (dia_semana BETWEEN 1 AND 7)
        ");
        DB::statement("
            ALTER TABLE carga_horaria
            ADD CONSTRAINT chk_carga_horaria_horas
            CHECK (hora_fin > hora_inicio)
        ");
        DB::statement("
            ALTER TABLE carga_horaria
            ADD CONSTRAINT chk_carga_horaria_estado
            CHECK (estado IN ('Vigente','Modificado','Anulado'))
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE carga_horaria DROP CONSTRAINT IF EXISTS chk_carga_horaria_estado");
        DB::statement("ALTER TABLE carga_horaria DROP CONSTRAINT IF EXISTS chk_carga_horaria_horas");
        DB::statement("ALTER TABLE carga_horaria DROP CONSTRAINT IF EXISTS chk_carga_horaria_dia");
        Schema::dropIfExists('carga_horaria');
    }
};
