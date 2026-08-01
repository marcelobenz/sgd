<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iso_periodo_transiciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('iso_periodos')->restrictOnDelete();
            $table->enum('accion', ['cierre', 'reapertura']);
            $table->string('estado_anterior');
            $table->string('estado_nuevo');
            $table->text('motivo');
            $table->json('resumen_control')->nullable();
            $table->foreignId('realizado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['periodo_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iso_periodo_transiciones');
    }
};
