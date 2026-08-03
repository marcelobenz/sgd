<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iso_proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('producto_servicio');
            $table->string('area_responsable');
            $table->date('fecha_alta')->nullable();
            $table->enum('criticidad', ['critico', 'no_critico'])->default('no_critico');
            $table->unsignedSmallInteger('periodicidad_meses')->default(12);
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->text('observaciones')->nullable();
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->text('enlace_externo')->nullable();
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('actualizado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['nombre', 'producto_servicio']);
            $table->index(['estado', 'criticidad']);
        });

        Schema::create('iso_proveedor_selecciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('iso_proveedores')->cascadeOnDelete();
            $table->date('fecha');
            $table->json('calificaciones');
            $table->decimal('puntaje', 4, 2);
            $table->enum('resultado', ['aprobado', 'condicional', 'no_aprobado']);
            $table->text('conclusion');
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->text('enlace_externo')->nullable();
            $table->foreignId('evaluado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('iso_proveedor_evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('iso_proveedores')->cascadeOnDelete();
            $table->foreignId('periodo_id')->nullable()->constrained('iso_periodos')->nullOnDelete();
            $table->date('fecha_evaluacion');
            $table->json('calificaciones');
            $table->decimal('puntaje', 4, 2);
            $table->enum('resultado', ['aprobado', 'condicional', 'no_aprobado']);
            $table->enum('decision', ['continuar', 'continuar_con_acciones', 'reemplazar', 'suspender']);
            $table->text('conclusion');
            $table->text('justificacion')->nullable();
            $table->date('proxima_evaluacion')->nullable();
            $table->boolean('requiere_analisis_riesgo')->default(false);
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->text('enlace_externo')->nullable();
            $table->foreignId('evaluado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['resultado', 'proxima_evaluacion']);
        });

        Schema::create('iso_proveedor_evaluacion_riesgo', function (Blueprint $table) {
            $table->foreignId('evaluacion_id')->constrained('iso_proveedor_evaluaciones')->cascadeOnDelete();
            $table->foreignId('riesgo_id')->constrained('iso_riesgos')->cascadeOnDelete();
            $table->primary(['evaluacion_id', 'riesgo_id']);
        });

        Schema::create('iso_proveedor_acciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('iso_proveedor_evaluaciones')->cascadeOnDelete();
            $table->text('descripcion');
            $table->string('area_responsable');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_objetivo');
            $table->enum('estado', ['pendiente', 'en_proceso', 'completada', 'cancelada'])->default('pendiente');
            $table->text('resultado')->nullable();
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->text('enlace_externo')->nullable();
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('actualizado_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('cerrada_en')->nullable();
            $table->timestamps();
            $table->index(['estado', 'fecha_objetivo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iso_proveedor_acciones');
        Schema::dropIfExists('iso_proveedor_evaluacion_riesgo');
        Schema::dropIfExists('iso_proveedor_evaluaciones');
        Schema::dropIfExists('iso_proveedor_selecciones');
        Schema::dropIfExists('iso_proveedores');
    }
};
