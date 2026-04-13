<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('recordatorio_ejecuciones', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('documento_recordatorio_id');
            $table->unsignedBigInteger('documento_id');
            $table->unsignedBigInteger('user_id');

            $table->dateTime('fecha_programada');

            $table->enum('estado', ['pendiente', 'resuelto', 'vencido', 'postergado'])
                ->default('pendiente');

            $table->dateTime('fecha_resolucion')->nullable();
            $table->dateTime('postergado_hasta')->nullable();

            $table->text('observacion')->nullable();

            $table->timestamps();

            $table->foreign('documento_recordatorio_id')
                ->references('id')->on('documento_recordatorios')
                ->onDelete('cascade');

            $table->foreign('documento_id')
                ->references('id')->on('documentos')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->index(['user_id', 'estado']);
            $table->index(['fecha_programada']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recordatorio_ejecuciones');
    }
};
