<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iso_riesgos', function (Blueprint $table) {
            $table->text('criterio_eficacia')->nullable()->after('efecto_potencial');
        });

        Schema::create('iso_riesgo_verificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riesgo_id')->constrained('iso_riesgos')->restrictOnDelete();
            $table->enum('tipo', ['intermedia', 'final']);
            $table->date('fecha');
            $table->enum('eficacia', ['si', 'parcial', 'no']);
            $table->text('conclusion');
            $table->unsignedTinyInteger('impacto')->nullable();
            $table->unsignedTinyInteger('probabilidad')->nullable();
            $table->unsignedTinyInteger('indice')->nullable();
            $table->enum('estado_resultante', ['en_proceso', 'permanente', 'finalizado'])->nullable();
            $table->text('justificacion_excepcion')->nullable();
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->text('enlace_externo')->nullable();
            $table->foreignId('verificado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['riesgo_id', 'tipo', 'fecha']);
        });

        foreach (DB::table('iso_riesgos')->whereIn('eficacia', ['si', 'parcial', 'no'])->orderBy('id')->get() as $riesgo) {
            DB::table('iso_riesgo_verificaciones')->insert([
                'riesgo_id' => $riesgo->id,
                'tipo' => 'final',
                'fecha' => substr((string) $riesgo->updated_at, 0, 10),
                'eficacia' => $riesgo->eficacia,
                'conclusion' => $riesgo->conclusion_eficacia ?: 'Verificación migrada desde el registro anterior.',
                'impacto' => $riesgo->impacto_final,
                'probabilidad' => $riesgo->probabilidad_final,
                'indice' => $riesgo->indice_final,
                'estado_resultante' => in_array($riesgo->estado, ['en_proceso', 'permanente', 'finalizado']) ? $riesgo->estado : 'en_proceso',
                'justificacion_excepcion' => 'Registro histórico migrado automáticamente.',
                'documento_id' => null,
                'enlace_externo' => null,
                'verificado_por' => $riesgo->actualizado_por,
                'created_at' => $riesgo->updated_at,
                'updated_at' => $riesgo->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('iso_riesgo_verificaciones');
        Schema::table('iso_riesgos', function (Blueprint $table) {
            $table->dropColumn('criterio_eficacia');
        });
    }
};
