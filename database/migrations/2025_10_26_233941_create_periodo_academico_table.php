<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('periodo_academico', function (Blueprint $table) {
            $table->increments('id_periodo'); // SERIAL
            $table->string('nombre', 50)->unique(); // ej. 2025-1
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activo')->default(true);
            $table->string('estado_publicacion', 20)->default('Borrador'); // Borrador | Publicado | Reabierto
        });


        DB::statement("ALTER TABLE periodo_academico
            DROP CONSTRAINT IF EXISTS chk_periodo_estado_publicacion");
        // CHECK constraint (PostgreSQL)
        DB::statement("
            ALTER TABLE periodo_academico
            ADD CONSTRAINT chk_periodo_estado_publicacion
            CHECK (
                LOWER(estado_publicacion) IN ('borrador','publicado','archivado')
                AND (LOWER(estado_publicacion) = 'borrador' OR activo = false)
            )"
        );
    }

    public function down(): void
    {
        // Quitar CHECK antes de dropear (PostgreSQL ignora si no existe)
        DB::statement("ALTER TABLE periodo_academico DROP CONSTRAINT IF EXISTS chk_periodo_estado_publicacion");
        Schema::dropIfExists('periodo_academico');
    }
};
