<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist;');
        }
    }

    public function down(): void
    {
        // if (DB::getDriverName() === 'pgsql') {
        //     DB::statement('DROP EXTENSION IF EXISTS btree_gist;');
        // }
    }
};
