<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesion_docente_token', function (Blueprint $table) {
            $table->id('id_token');
            $table->unsignedBigInteger('id_carga');
            $table->date('fecha_sesion');
            $table->string('token', 64)->unique();
            $table->timestamp('vence_en');
            $table->timestamp('creado_en')->useCurrent();

            $table->unique(['id_carga', 'fecha_sesion'], 'uq_token_por_sesion');

            $table->foreign('id_carga')
                ->references('id_carga')->on('carga_horaria')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesion_docente_token');
    }
};
