<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Alinear ENUM en historial_documentos
        DB::statement("
            ALTER TABLE historial_documentos 
            MODIFY COLUMN estado ENUM('en curso','pendiente de aprobación','aprobado','registro') NOT NULL
        ");

    }

    public function down(): void
    {
        // Revertir a la lista anterior (ajustá si tu lista previa era distinta)
        DB::statement("
            ALTER TABLE historial_documentos 
            MODIFY COLUMN estado ENUM('en curso','pendiente de aprobación','aprobado') NOT NULL
        ");

    }
};
