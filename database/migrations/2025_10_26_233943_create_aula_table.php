<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aula', function (Blueprint $table) {
            $table->increments('id_aula');
            $table->string('nombre_aula', 50)->unique();
            $table->integer('capacidad')->nullable();
            $table->string('tipo_aula', 30)->default('Convencional'); // Laboratorio, Auditorio
            $table->string('ubicacion', 100)->nullable();
            $table->boolean('habilitado')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aula');
    }
};
