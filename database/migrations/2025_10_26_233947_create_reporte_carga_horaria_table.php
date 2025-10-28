<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporte_carga_horaria', function (Blueprint $table) {
            $table->id('id_reporte');

            $table->unsignedBigInteger('id_docente')->nullable(false);
            $table->unsignedBigInteger('id_periodo')->nullable(false);

            $table->decimal('total_horas_programadas', 10, 2)->nullable(false);
            $table->decimal('total_horas_ausencia', 10, 2)->default(0);

            $table->timestamp('fecha_generacion')->useCurrent();
            $table->string('tipo_reporte', 50)->nullable();

            $table->foreign('id_docente')
                ->references('id_docente')->on('docente')
                ->restrictOnDelete();

            $table->foreign('id_periodo')
                ->references('id_periodo')->on('periodo_academico')
                ->restrictOnDelete();

            // Índices comunes de consulta
            $table->index(['id_docente', 'id_periodo'], 'idx_reporte_docente_periodo');
            $table->index(['fecha_generacion'], 'idx_reporte_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte_carga_horaria');
    }
};
