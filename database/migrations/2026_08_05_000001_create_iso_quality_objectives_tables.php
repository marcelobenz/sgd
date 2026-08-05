<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iso_objetivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('iso_periodos')->restrictOnDelete();
            $table->unsignedInteger('numero');
            $table->string('codigo', 30)->unique();
            $table->text('compromiso_politica');
            $table->string('proceso');
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('area_responsable');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_objetivo');
            $table->string('periodicidad_seguimiento', 50);
            $table->string('estado', 30)->default('activo');
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['periodo_id', 'numero']);
            $table->index(['periodo_id', 'estado']);
            $table->index('fecha_objetivo');
        });

        Schema::create('iso_objetivo_indicadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objetivo_id')->constrained('iso_objetivos')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('metodo_calculo');
            $table->string('unidad', 50);
            $table->string('fuente');
            $table->string('frecuencia', 50);
            $table->string('agregacion', 30)->default('ultimo');
            $table->string('comparador', 30);
            $table->decimal('meta', 18, 4)->nullable();
            $table->decimal('meta_hasta', 18, 4)->nullable();
            $table->decimal('tolerancia', 18, 4)->nullable();
            $table->decimal('linea_base', 18, 4)->nullable();
            $table->boolean('principal')->default(true);
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('iso_objetivo_mediciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')->constrained('iso_objetivo_indicadores')->cascadeOnDelete();
            $table->date('fecha_medicion');
            $table->string('periodo_referencia', 100)->nullable();
            $table->decimal('valor', 18, 4);
            $table->text('observaciones')->nullable();
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->string('enlace_externo', 2000)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['indicador_id', 'fecha_medicion']);
        });

        Schema::create('iso_objetivo_acciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objetivo_id')->constrained('iso_objetivos')->cascadeOnDelete();
            $table->text('descripcion');
            $table->string('area_responsable');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_objetivo');
            $table->string('estado', 30)->default('pendiente');
            $table->text('resultado')->nullable();
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->string('enlace_externo', 2000)->nullable();
            $table->dateTime('cerrada_en')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['objetivo_id', 'estado']);
            $table->index('fecha_objetivo');
        });

        Schema::create('iso_objetivo_accion_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accion_id')->constrained('iso_objetivo_acciones')->cascadeOnDelete();
            $table->date('fecha');
            $table->text('detalle');
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->string('enlace_externo', 2000)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('iso_objetivo_accion_transiciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accion_id')->constrained('iso_objetivo_acciones')->cascadeOnDelete();
            $table->string('estado_anterior', 30);
            $table->string('estado_nuevo', 30);
            $table->text('motivo');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('iso_objetivo_evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objetivo_id')->constrained('iso_objetivos')->cascadeOnDelete();
            $table->date('fecha_evaluacion');
            $table->decimal('resultado', 18, 4)->nullable();
            $table->string('cumplimiento', 30);
            $table->text('conclusion');
            $table->text('justificacion')->nullable();
            $table->string('decision', 30);
            $table->date('proxima_evaluacion')->nullable();
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->string('enlace_externo', 2000)->nullable();
            $table->foreignId('evaluado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['objetivo_id', 'fecha_evaluacion']);
        });

        Schema::create('iso_objetivo_contexto', function (Blueprint $table) {
            $table->foreignId('objetivo_id')->constrained('iso_objetivos')->cascadeOnDelete();
            $table->foreignId('contexto_id')->constrained('iso_contextos')->cascadeOnDelete();
            $table->primary(['objetivo_id', 'contexto_id']);
        });

        Schema::create('iso_objetivo_riesgo', function (Blueprint $table) {
            $table->foreignId('objetivo_id')->constrained('iso_objetivos')->cascadeOnDelete();
            $table->foreignId('riesgo_id')->constrained('iso_riesgos')->cascadeOnDelete();
            $table->primary(['objetivo_id', 'riesgo_id']);
        });

        Schema::create('iso_objetivo_parte', function (Blueprint $table) {
            $table->foreignId('objetivo_id')->constrained('iso_objetivos')->cascadeOnDelete();
            $table->foreignId('parte_id')->constrained('iso_partes_interesadas')->cascadeOnDelete();
            $table->primary(['objetivo_id', 'parte_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iso_objetivo_parte');
        Schema::dropIfExists('iso_objetivo_riesgo');
        Schema::dropIfExists('iso_objetivo_contexto');
        Schema::dropIfExists('iso_objetivo_evaluaciones');
        Schema::dropIfExists('iso_objetivo_accion_transiciones');
        Schema::dropIfExists('iso_objetivo_accion_seguimientos');
        Schema::dropIfExists('iso_objetivo_acciones');
        Schema::dropIfExists('iso_objetivo_mediciones');
        Schema::dropIfExists('iso_objetivo_indicadores');
        Schema::dropIfExists('iso_objetivos');
    }
};
