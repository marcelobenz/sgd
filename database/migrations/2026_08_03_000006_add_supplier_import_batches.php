<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iso_proveedor_importaciones', function (Blueprint $table) {
            $table->uuid('lote')->primary();
            $table->string('archivo');
            $table->string('checksum', 64)->index();
            $table->enum('estado', ['importado', 'revertido'])->default('importado');
            $table->json('resumen');
            $table->foreignId('ejecutado_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('revertido_en')->nullable();
            $table->foreignId('revertido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        foreach (['iso_proveedores', 'iso_proveedor_selecciones', 'iso_proveedor_evaluaciones'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $table) => $table->uuid('importacion_lote')->nullable()->index());
        }
    }

    public function down(): void
    {
        foreach (['iso_proveedor_evaluaciones', 'iso_proveedor_selecciones', 'iso_proveedores'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $table) => $table->dropColumn('importacion_lote'));
        }
        Schema::dropIfExists('iso_proveedor_importaciones');
    }
};
