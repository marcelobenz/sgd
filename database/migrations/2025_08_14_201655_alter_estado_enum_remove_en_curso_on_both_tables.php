<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Quitar "en curso" del ENUM en documentos
        DB::statement("
            ALTER TABLE documentos
            MODIFY COLUMN estado
            ENUM('registro', 'pendiente de aprobación', 'aprobado')
            NOT NULL DEFAULT 'registro'
        ");

        // Quitar "en curso" del ENUM en historial_documentos
        DB::statement("
            ALTER TABLE historial_documentos
            MODIFY COLUMN estado
            ENUM('registro', 'pendiente de aprobación', 'aprobado')
            NOT NULL DEFAULT 'registro'
        ");
    }

    public function down(): void
    {
        // Volver a incluir "en curso" en documentos
        DB::statement("
            ALTER TABLE documentos
            MODIFY COLUMN estado
            ENUM('registro', 'en curso', 'pendiente de aprobación', 'aprobado')
            NOT NULL DEFAULT 'registro'
        ");

        // Volver a incluir "en curso" en historial_documentos
        DB::statement("
            ALTER TABLE historial_documentos
            MODIFY COLUMN estado
            ENUM('registro', 'en curso', 'pendiente de aprobación', 'aprobado')
            NOT NULL DEFAULT 'registro'
        ");
    }
};
