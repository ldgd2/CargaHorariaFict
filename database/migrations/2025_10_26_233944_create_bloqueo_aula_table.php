<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bloqueo_aula', function (Blueprint $table) {
            $table->increments('id_bloqueo');
            $table->integer('id_aula');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->text('motivo');
            $table->integer('registrado_por')->nullable();

            // FKs
            $table->foreign('id_aula')->references('id_aula')->on('aula')->cascadeOnDelete();
            $table->foreign('registrado_por')->references('id_usuario')->on('usuario')->nullOnDelete();
        });

        // CHECK fechas
        DB::statement("
            ALTER TABLE bloqueo_aula
            ADD CONSTRAINT chk_bloqueo_aula_fechas
            CHECK (fecha_fin >= fecha_inicio)
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE bloqueo_aula DROP CONSTRAINT IF EXISTS chk_bloqueo_aula_fechas");
        Schema::dropIfExists('bloqueo_aula');
    }
};
