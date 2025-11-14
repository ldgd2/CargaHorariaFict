<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('conflictos_auditoria', function (Blueprint $table) {
            $table->id('id_conflicto'); // SERIAL PRIMARY KEY
            
            // ID del periodo (FK, ON DELETE CASCADE)
            $table->foreignId('periodo_id')
                  ->constrained('periodo_academico', 'id_periodo')
                  ->onDelete('cascade');

            // ID de la carrera (FK, ON DELETE SET NULL)
            $table->foreignId('carrera_id')
                  ->nullable()
                  ->constrained('carrera', 'id_carrera')
                  ->onDelete('set null');

            // ID del grupo (FK, ON DELETE SET NULL)
            $table->foreignId('grupo_id')
                  ->nullable()
                  ->constrained('grupo', 'id_grupo')
                  ->onDelete('set null');
            
            // Campos de texto y tipo
            $table->string('tipo', 50); // Tipo de conflicto
            $table->text('descripcion'); // Descripción detallada del error

            // IDs de las cargas horarias en conflicto (FK, ON DELETE SET NULL)
            $table->foreignId('carga1_id')
                  ->nullable()
                  ->constrained('carga_horaria', 'id_carga')
                  ->onDelete('set null');
            $table->foreignId('carga2_id')
                  ->nullable()
                  ->constrained('carga_horaria', 'id_carga')
                  ->onDelete('set null');

            // Timestamps para Laravel
            $table->timestamps();

            // Índices adicionales (los foreignId ya crean índices por defecto, pero los añadiremos por claridad)
            // (Los índices creados por los foreignId son suficientes y no requieren índices duplicados)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conflictos_auditoria');
    }
};