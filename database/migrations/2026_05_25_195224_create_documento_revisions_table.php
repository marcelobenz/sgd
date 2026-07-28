<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documento_revisiones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('documento_id')
                ->constrained('documentos')
                ->onDelete('cascade');

            $table->unsignedBigInteger('recordatorio_id')->nullable();

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict');

            $table->dateTime('fecha_revision');
            $table->string('resultado')->default('conforme');
            $table->text('observacion')->nullable();
            $table->boolean('requiere_nueva_version')->default(false);
            $table->unsignedBigInteger('version_generada_id')->nullable();
            $table->string('archivo_evidencia')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documento_revisions');
    }
};
