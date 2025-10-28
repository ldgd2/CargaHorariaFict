<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id('id_bitacora');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('fecha_hora')->useCurrent();
            $table->string('entidad', 50);
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->string('accion', 100);
            $table->text('descripcion')->nullable();
            $table->jsonb('datos_anteriores')->nullable();
            $table->jsonb('datos_nuevos')->nullable();

            $table->foreign('user_id')
                ->references('id_usuario')->on('usuario')
                ->nullOnDelete();

            // Índices útiles
            $table->index(['entidad', 'entidad_id'], 'idx_bitacora_entidad');
            $table->index(['fecha_hora'], 'idx_bitacora_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
