<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reapertura_historial', function (Blueprint $table) {
            $table->id('id_historial');
            $table->unsignedBigInteger('id_periodo');
            $table->timestamp('fecha_hora')->useCurrent();
            $table->text('motivo');
            $table->unsignedBigInteger('autorizado_por')->nullable();

            $table->foreign('id_periodo')
                ->references('id_periodo')->on('periodo_academico')
                ->cascadeOnDelete();

            $table->foreign('autorizado_por')
                ->references('id_usuario')->on('usuario')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reapertura_historial');
    }
};
