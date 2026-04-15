<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recordatorio_ejecuciones', function (Blueprint $table) {
            $table->unsignedBigInteger('resuelto_por_user_id')->nullable()->after('user_id');
            $table->text('observacion_resolucion')->nullable()->after('observacion');

            $table->foreign('resuelto_por_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recordatorio_ejecuciones', function (Blueprint $table) {
            $table->dropForeign(['resuelto_por_user_id']);
            $table->dropColumn(['resuelto_por_user_id', 'observacion_resolucion']);
        });
    }
};