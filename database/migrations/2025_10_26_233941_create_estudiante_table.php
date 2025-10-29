<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('estudiante', function (Blueprint $table) {
            // PK elegida por el negocio
            $table->string('codigo_universitario', 20)->primary();

            // Relación 1:1 con usuario: si se borra el usuario, se borra el estudiante
            $table->unsignedBigInteger('id_usuario')->unique();

            // Datos de estudiante
            $table->string('carrera', 100);
            $table->integer('semestre')->nullable();

            // Clave foránea a usuario(id_usuario) con cascada
            $table->foreign('id_usuario')
                ->references('id_usuario')->on('usuario')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('estudiante', function (Blueprint $table) {
       
            $table->dropForeign(['id_usuario']);
        });

        Schema::dropIfExists('estudiante');
    }
};
