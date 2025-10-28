<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('materia_carrera', function (Blueprint $table) {
            $table->unsignedInteger('id_materia');
            $table->unsignedInteger('id_carrera');

            // PK compuesta
            $table->primary(['id_materia', 'id_carrera']);

            // FKs con cascada
            $table->foreign('id_materia')
                  ->references('id_materia')->on('materia')
                  ->onDelete('cascade');

            $table->foreign('id_carrera')
                  ->references('id_carrera')->on('carrera')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia_carrera');
    }
};
