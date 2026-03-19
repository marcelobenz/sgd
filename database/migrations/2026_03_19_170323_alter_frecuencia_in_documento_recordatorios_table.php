<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE documento_recordatorios
            MODIFY frecuencia ENUM('no_repite','diario','semanal','mensual','anual') NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE documento_recordatorios
            MODIFY frecuencia ENUM('diario','semanal','mensual','anual') NOT NULL
        ");
    }
};