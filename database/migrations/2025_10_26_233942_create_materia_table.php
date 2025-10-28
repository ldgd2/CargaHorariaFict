<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('materia', function (Blueprint $table) {
            $table->increments('id_materia'); // SERIAL
            $table->string('cod_materia', 20)->unique(); // INF101
            $table->string('nombre', 100)->unique();
            $table->integer('creditos');
            $table->integer('horas_semanales')->default(4);
            $table->text('programa')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia');
    }
};
