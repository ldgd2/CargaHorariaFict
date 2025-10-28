<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('estudiante', function (Blueprint $table) {
            // PK que además es FK a usuario(id_usuario)
            $table->unsignedInteger('id_estudiante')->primary();

            $table->string('codigo_universitario', 20)->unique();
            $table->string('carrera', 100);
            $table->integer('semestre')->nullable();

            $table->foreign('id_estudiante')
                ->references('id_usuario')->on('usuario')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante');
    }
};
