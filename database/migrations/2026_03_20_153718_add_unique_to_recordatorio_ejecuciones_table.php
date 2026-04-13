<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recordatorio_ejecuciones', function (Blueprint $table) {
            $table->unique(
                ['documento_recordatorio_id', 'documento_id', 'user_id', 'fecha_programada'],
                'uniq_recordatorio_ejecucion_usuario_fecha'
            );
        });
    }

    public function down(): void
    {
        Schema::table('recordatorio_ejecuciones', function (Blueprint $table) {
            $table->dropUnique('uniq_recordatorio_ejecucion_usuario_fecha');
        });
    }
};