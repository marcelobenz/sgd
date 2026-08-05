<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iso_objetivo_revisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objetivo_id')->constrained('iso_objetivos')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->date('fecha_vigencia');
            $table->text('motivo');
            $table->json('valores_anteriores');
            $table->json('valores_nuevos');
            $table->foreignId('realizada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['objetivo_id', 'fecha_vigencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iso_objetivo_revisiones');
    }
};
