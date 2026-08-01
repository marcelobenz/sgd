<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iso_periodos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio')->unique();
            $table->string('nombre');
            $table->enum('estado', ['borrador', 'vigente', 'cerrado'])->default('borrador');
            $table->boolean('cambio_climatico_relevante')->nullable();
            $table->text('fundamento_cambio_climatico')->nullable();
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('cerrado_en')->nullable();
            $table->timestamps();
        });

        Schema::create('iso_contextos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('iso_periodos')->restrictOnDelete();
            $table->enum('tipo', ['fortaleza', 'debilidad', 'oportunidad', 'amenaza']);
            $table->unsignedSmallInteger('numero');
            $table->string('codigo')->unique();
            $table->string('titulo');
            $table->text('descripcion');
            $table->string('proceso')->nullable();
            $table->text('fuente')->nullable();
            $table->date('fecha_identificacion');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('relevante_sgc')->nullable();
            $table->enum('decision', ['pendiente', 'tratar_riesgo', 'aprovechar_oportunidad', 'vincular_existente', 'aceptar_sin_accion', 'no_aplicable'])->default('pendiente');
            $table->text('justificacion')->nullable();
            $table->string('referencia_existente')->nullable();
            $table->enum('estado', ['activo', 'archivado', 'anulado'])->default('activo');
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('actualizado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['periodo_id', 'tipo', 'numero']);
            $table->index(['periodo_id', 'decision']);
        });

        Schema::create('iso_riesgos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('iso_periodos')->restrictOnDelete();
            $table->foreignId('contexto_id')->nullable()->constrained('iso_contextos')->restrictOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->string('codigo')->unique();
            $table->enum('tipo', ['riesgo', 'oportunidad']);
            $table->string('proceso');
            $table->text('identificacion');
            $table->text('partes_interesadas')->nullable();
            $table->text('efecto_potencial');
            $table->unsignedTinyInteger('impacto_inicial');
            $table->unsignedTinyInteger('probabilidad_inicial');
            $table->unsignedTinyInteger('indice_inicial');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_verificacion_prevista')->nullable();
            $table->enum('eficacia', ['pendiente', 'si', 'parcial', 'no'])->default('pendiente');
            $table->text('conclusion_eficacia')->nullable();
            $table->unsignedTinyInteger('impacto_final')->nullable();
            $table->unsignedTinyInteger('probabilidad_final')->nullable();
            $table->unsignedTinyInteger('indice_final')->nullable();
            $table->enum('estado', ['pendiente', 'en_proceso', 'permanente', 'finalizado', 'anulado'])->default('pendiente');
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('actualizado_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('finalizado_en')->nullable();
            $table->timestamps();
            $table->unique(['periodo_id', 'numero']);
            $table->index(['periodo_id', 'tipo', 'estado']);
        });

        Schema::create('iso_acciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riesgo_id')->constrained('iso_riesgos')->restrictOnDelete();
            $table->text('descripcion');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_objetivo');
            $table->enum('estado', ['pendiente', 'en_proceso', 'completada', 'cancelada'])->default('pendiente');
            $table->text('resultado')->nullable();
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('actualizado_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('completada_en')->nullable();
            $table->timestamps();
            $table->index(['responsable_id', 'estado', 'fecha_objetivo']);
        });

        Schema::create('iso_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accion_id')->constrained('iso_acciones')->cascadeOnDelete();
            $table->date('fecha');
            $table->text('detalle');
            $table->string('resultado')->nullable();
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->text('enlace_externo')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('iso_partes_interesadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('iso_periodos')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('necesidades_requisitos');
            $table->string('proceso')->nullable();
            $table->boolean('relevante_sgc')->default(true);
            $table->text('seguimiento')->nullable();
            $table->enum('estado', ['activa', 'archivada'])->default('activa');
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('actualizado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('iso_usuario_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('puede_ver')->default(true);
            $table->boolean('puede_gestionar')->default(false);
            $table->boolean('puede_administrar')->default(false);
            $table->timestamps();
        });

        Schema::create('iso_historial_cambios', function (Blueprint $table) {
            $table->id();
            $table->string('entidad_tipo');
            $table->unsignedBigInteger('entidad_id');
            $table->string('evento');
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['entidad_tipo', 'entidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iso_historial_cambios');
        Schema::dropIfExists('iso_usuario_permisos');
        Schema::dropIfExists('iso_partes_interesadas');
        Schema::dropIfExists('iso_seguimientos');
        Schema::dropIfExists('iso_acciones');
        Schema::dropIfExists('iso_riesgos');
        Schema::dropIfExists('iso_contextos');
        Schema::dropIfExists('iso_periodos');
    }
};
