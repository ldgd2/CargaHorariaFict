<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencia_sesion', function (Blueprint $table) {
            $table->id('id_asistencia');
            $table->unsignedBigInteger('id_carga');
            $table->date('fecha_sesion');
            $table->time('hora_registro')->useCurrent();
            $table->string('tipo_registro', 20); // 'QR' | 'Manual'
            $table->unsignedBigInteger('registrado_por')->nullable(); // usuario (docente/admin)
            $table->string('estado', 30)->default('Presente'); // 'Presente','Manual Validado','Pendiente','Anulado'
            $table->text('motivo')->nullable(); // si es Manual

            $table->unique(['id_carga', 'fecha_sesion'], 'uq_asistencia_por_sesion');

            $table->foreign('id_carga')
                ->references('id_carga')->on('carga_horaria')
                ->restrictOnDelete();

            $table->foreign('registrado_por')
                ->references('id_usuario')->on('usuario')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_sesion');
    }
};
