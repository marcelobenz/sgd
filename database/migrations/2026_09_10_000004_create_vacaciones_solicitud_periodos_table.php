<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacaciones_solicitud_periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacaciones_solicitud_id')->constrained('vacaciones_solicitudes')->cascadeOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->unsignedSmallInteger('dias');
            $table->timestamps();
            $table->unique(['vacaciones_solicitud_id', 'anio'], 'vsp_solicitud_anio_unique');
            $table->index(['anio', 'vacaciones_solicitud_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacaciones_solicitud_periodos');
    }
};
