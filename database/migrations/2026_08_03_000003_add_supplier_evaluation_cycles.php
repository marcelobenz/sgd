<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iso_proveedor_evaluaciones', function (Blueprint $table) {
            $table->enum('tipo', ['periodica', 'reevaluacion'])->default('periodica')->after('fecha_evaluacion');
            $table->foreignId('evaluacion_anterior_id')->nullable()->after('tipo')->constrained('iso_proveedor_evaluaciones')->nullOnDelete();
            $table->enum('estado_ciclo', ['cerrada', 'en_tratamiento', 'pendiente_reevaluacion'])->default('cerrada')->after('decision');
            $table->index(['proveedor_id', 'estado_ciclo'], 'iso_proveedor_ciclo_idx');
        });
        Schema::table('iso_proveedor_acciones', function (Blueprint $table) {
            $table->boolean('obligatoria')->default(true)->after('evaluacion_id');
        });

        DB::statement("UPDATE iso_proveedor_evaluaciones e SET e.estado_ciclo = 'en_tratamiento' WHERE e.resultado IN ('condicional','no_aprobado') AND e.decision IN ('continuar','continuar_con_acciones') AND EXISTS (SELECT 1 FROM iso_proveedor_acciones a WHERE a.evaluacion_id = e.id AND a.obligatoria = 1 AND a.estado NOT IN ('completada','cancelada'))");
        DB::statement("UPDATE iso_proveedor_evaluaciones e SET e.estado_ciclo = 'pendiente_reevaluacion' WHERE e.resultado IN ('condicional','no_aprobado') AND e.decision IN ('continuar','continuar_con_acciones') AND EXISTS (SELECT 1 FROM iso_proveedor_acciones a WHERE a.evaluacion_id = e.id AND a.obligatoria = 1) AND NOT EXISTS (SELECT 1 FROM iso_proveedor_acciones a WHERE a.evaluacion_id = e.id AND a.obligatoria = 1 AND a.estado NOT IN ('completada','cancelada'))");
    }

    public function down(): void
    {
        Schema::table('iso_proveedor_acciones', function (Blueprint $table) {
            $table->dropColumn('obligatoria');
        });
        Schema::table('iso_proveedor_evaluaciones', function (Blueprint $table) {
            $table->dropForeign(['evaluacion_anterior_id']);
            $table->dropIndex('iso_proveedor_ciclo_idx');
            $table->dropColumn(['tipo', 'evaluacion_anterior_id', 'estado_ciclo']);
        });
    }
};
