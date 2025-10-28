<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('docente', function (Blueprint $table) {
            // PK que además es FK a usuario(id_usuario)
            $table->unsignedInteger('id_docente')->primary();

            $table->string('nro_documento', 30)->nullable();
            $table->string('tipo_contrato', 20)->nullable(); // TC, MT, PH
            $table->string('carrera_principal', 100)->nullable();
            $table->decimal('tope_horas_semana', 5, 2)->default(40.00);
            $table->boolean('habilitado')->default(true);

            $table->foreign('id_docente')
                ->references('id_usuario')->on('usuario')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente');
    }
};
