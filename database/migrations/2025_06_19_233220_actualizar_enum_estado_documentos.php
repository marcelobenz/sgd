<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE documentos MODIFY COLUMN estado ENUM('en curso', 'pendiente de aprobación', 'aprobado', 'registro') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en curso'");
    }

    public function down()
    {
        // Volver al estado anterior (sin 'registro')
        DB::statement("ALTER TABLE documentos MODIFY COLUMN estado ENUM('en curso', 'pendiente de aprobación', 'aprobado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en curso'");
    }
};
