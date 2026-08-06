<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iso_riesgos', function (Blueprint $table) {
            $table->foreignId('riesgo_origen_id')->nullable()->after('periodo_id')
                ->constrained('iso_riesgos')->restrictOnDelete();
            $table->unique(['periodo_id', 'riesgo_origen_id'], 'iso_riesgos_periodo_origen_unique');
        });
    }

    public function down(): void
    {
        Schema::table('iso_riesgos', function (Blueprint $table) {
            $table->dropUnique('iso_riesgos_periodo_origen_unique');
            $table->dropConstrainedForeignId('riesgo_origen_id');
        });
    }
};
