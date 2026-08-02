<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iso_riesgo_transiciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riesgo_id')->constrained('iso_riesgos')->cascadeOnDelete();
            $table->enum('accion', ['reapertura_por_accion']);
            $table->string('estado_anterior');
            $table->string('estado_nuevo');
            $table->text('motivo');
            $table->foreignId('realizado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['riesgo_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iso_riesgo_transiciones');
    }
};
