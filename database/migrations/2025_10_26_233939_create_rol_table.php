<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rol', function (Blueprint $table) {
            $table->increments('id_rol'); // SERIAL
            $table->string('nombre_rol', 50)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('habilitado')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol');
    }
};
