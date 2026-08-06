<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iso_objetivos', function (Blueprint $table) {
            $table->foreignId('objetivo_origen_id')->nullable()->after('periodo_id')
                ->constrained('iso_objetivos')->restrictOnDelete();
            $table->unique(['periodo_id', 'objetivo_origen_id'], 'iso_objetivos_periodo_origen_unique');
        });
        Schema::table('iso_objetivo_evaluaciones', function (Blueprint $table) {
            $table->string('tipo', 30)->default('seguimiento')->after('fecha_evaluacion');
            $table->index(['objetivo_id', 'tipo'], 'iso_obj_eval_objetivo_tipo_index');
        });
    }

    public function down(): void
    {
        Schema::table('iso_objetivo_evaluaciones', function (Blueprint $table) {
            $table->dropIndex('iso_obj_eval_objetivo_tipo_index');
            $table->dropColumn('tipo');
        });
        Schema::table('iso_objetivos', function (Blueprint $table) {
            $table->dropUnique('iso_objetivos_periodo_origen_unique');
            $table->dropConstrainedForeignId('objetivo_origen_id');
        });
    }
};
