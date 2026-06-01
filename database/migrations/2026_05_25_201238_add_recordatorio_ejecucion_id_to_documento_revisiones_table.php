<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documento_revisiones', function (Blueprint $table) {
            $table->unsignedBigInteger('recordatorio_ejecucion_id')
                ->nullable()
                ->after('recordatorio_id');
        });
    }

    public function down(): void
    {
        Schema::table('documento_revisiones', function (Blueprint $table) {
            $table->dropColumn('recordatorio_ejecucion_id');
        });
    }
};
