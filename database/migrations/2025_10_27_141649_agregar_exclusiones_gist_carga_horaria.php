<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Aseguramos la extensión antes (por si esta migración se corre sola)
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist;');

        // Evitar solapes por AULA (mismo día, rango solapado)
        DB::statement("
            ALTER TABLE carga_horaria
            ADD CONSTRAINT ex_aula
            EXCLUDE USING gist (
                id_aula WITH =,
                dia_semana WITH =,
                int4range(start_min, end_min) WITH &&
            );
        ");

        // Evitar solapes por DOCENTE (mismo día, rango solapado)
        DB::statement("
            ALTER TABLE carga_horaria
            ADD CONSTRAINT ex_docente
            EXCLUDE USING gist (
                id_docente WITH =,
                dia_semana WITH =,
                int4range(start_min, end_min) WITH &&
            );
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Eliminar las constraints si existen
        DB::statement("
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'ex_aula'
                ) THEN
                    ALTER TABLE carga_horaria DROP CONSTRAINT ex_aula;
                END IF;
                IF EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'ex_docente'
                ) THEN
                    ALTER TABLE carga_horaria DROP CONSTRAINT ex_docente;
                END IF;
            END
            $$;
        ");
    }
};
