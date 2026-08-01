<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iso_partes', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->string('nombre');
            $table->boolean('pertinente_sgc')->default(true);
            $table->text('necesidades_requisitos');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('metodo_medicion')->nullable();
            $table->string('proceso')->nullable();
            $table->boolean('requisitos_climaticos')->nullable();
            $table->text('detalle_climatico')->nullable();
            $table->enum('estado', ['activa', 'archivada'])->default('activa');
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('actualizado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['estado', 'pertinente_sgc']);
        });

        Schema::create('iso_parte_evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parte_id')->constrained('iso_partes')->restrictOnDelete();
            $table->foreignId('periodo_id')->nullable()->constrained('iso_periodos')->nullOnDelete();
            $table->date('fecha_evaluacion');
            $table->enum('resultado', ['cumplido', 'parcial', 'no_cumplido', 'no_evaluado']);
            $table->text('observaciones');
            $table->date('proxima_revision')->nullable();
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->text('enlace_externo')->nullable();
            $table->foreignId('evaluado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['parte_id', 'fecha_evaluacion']);
            $table->index(['periodo_id', 'resultado']);
        });

        Schema::create('iso_parte_evaluacion_riesgo', function (Blueprint $table) {
            $table->foreignId('evaluacion_id')->constrained('iso_parte_evaluaciones')->cascadeOnDelete();
            $table->foreignId('riesgo_id')->constrained('iso_riesgos')->restrictOnDelete();
            $table->primary(['evaluacion_id', 'riesgo_id']);
        });

        if (Schema::hasTable('iso_partes_interesadas')) {
            $partes = [];
            foreach (DB::table('iso_partes_interesadas')->orderBy('id')->get() as $legacy) {
                $clave = sha1(mb_strtolower(trim($legacy->nombre)));
                if (!isset($partes[$clave])) {
                    $parteId = DB::table('iso_partes')->insertGetId([
                        'clave' => $clave,
                        'nombre' => $legacy->nombre,
                        'pertinente_sgc' => (bool) $legacy->relevante_sgc,
                        'necesidades_requisitos' => $legacy->necesidades_requisitos,
                        'responsable_id' => null,
                        'metodo_medicion' => null,
                        'proceso' => $legacy->proceso,
                        'requisitos_climaticos' => null,
                        'detalle_climatico' => null,
                        'estado' => $legacy->estado === 'archivada' ? 'archivada' : 'activa',
                        'creado_por' => $legacy->creado_por,
                        'actualizado_por' => $legacy->actualizado_por,
                        'created_at' => $legacy->created_at,
                        'updated_at' => $legacy->updated_at,
                    ]);
                    $partes[$clave] = $parteId;
                } else {
                    $parteId = $partes[$clave];
                }

                if (filled($legacy->seguimiento)) {
                    DB::table('iso_parte_evaluaciones')->insert([
                        'parte_id' => $parteId,
                        'periodo_id' => $legacy->periodo_id,
                        'fecha_evaluacion' => substr((string) ($legacy->updated_at ?: $legacy->created_at), 0, 10),
                        'resultado' => 'no_evaluado',
                        'observaciones' => "Registro migrado: {$legacy->seguimiento}",
                        'proxima_revision' => null,
                        'documento_id' => null,
                        'enlace_externo' => null,
                        'evaluado_por' => $legacy->actualizado_por,
                        'created_at' => $legacy->created_at,
                        'updated_at' => $legacy->updated_at,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('iso_parte_evaluacion_riesgo');
        Schema::dropIfExists('iso_parte_evaluaciones');
        Schema::dropIfExists('iso_partes');
    }
};
