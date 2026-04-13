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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('path'); // Ruta del documento en S3
            $table->text('contenido')->nullable(); // Puedes hacer esto opcional si el contenido no siempre está presente
            $table->enum('estado', ['en curso', 'pendiente de aprobación', 'aprobado'])->default('en curso'); // Asegúrate de tener un valor por defecto
            $table->unsignedBigInteger('id_categoria');
            $table->unsignedBigInteger('id_usr_creador');
            $table->unsignedBigInteger('id_usr_ultima_modif');
            $table->timestamp('fecha_aprobacion')->nullable(); // Campo para la fecha de aprobación
            $table->Integer('version');
            $table->timestamps();

        
            // Definir las llaves foráneas
            $table->foreign('id_categoria')->references('id')->on('categorias')->onDelete('cascade');
            $table->foreign('id_usr_creador')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_usr_ultima_modif')->references('id')->on('users')->onDelete('cascade');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
