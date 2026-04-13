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
        Schema::create('historial_documentos', function (Blueprint $table) {
            $table->id();
            $table->string('path'); // Ruta del documento
            $table->string('titulo')->nullable();
            $table->text('contenido')->nullable();
            $table->enum('estado', ['en curso', 'pendiente de aprobación', 'aprobado'])->default('en curso');
            $table->unsignedBigInteger('id_documento');
            $table->unsignedBigInteger('id_categoria');
            $table->unsignedBigInteger('id_usr_creador');
            $table->unsignedBigInteger('id_usr_ultima_modif');
            $table->timestamp('fecha_aprobacion')->nullable(); // Campo para la fecha de aprobación
            $table->text('notas')->nullable(); // Detalles adicionales si los hubiera
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->Integer('version');
        
            // Foreign keys
            $table->foreign('id_documento')->references('id')->on('documentos')->onDelete('cascade');
            $table->foreign('id_categoria')->references('id')->on('categorias')->onDelete('restrict');
            $table->foreign('id_usr_creador')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('id_usr_ultima_modif')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_documentos');
    }
};
