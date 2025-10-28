<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('carrera', function (Blueprint $table) {
            $table->increments('id_carrera'); // SERIAL
            $table->string('nombre', 120)->unique();
            $table->unsignedInteger('jefe_docente_id')->nullable();
            $table->boolean('habilitado')->default(true);

            // FK a docente(id_docente)
            $table->foreign('jefe_docente_id')
                  ->references('id_docente')->on('docente')
                  ->nullOnDelete(); // ON DELETE SET NULL
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrera');
    }
};
