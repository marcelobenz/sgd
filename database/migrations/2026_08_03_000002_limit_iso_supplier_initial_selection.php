<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iso_proveedor_selecciones', function (Blueprint $table) {
            $table->unique('proveedor_id', 'iso_proveedor_seleccion_unica');
        });
    }

    public function down(): void
    {
        Schema::table('iso_proveedor_selecciones', function (Blueprint $table) {
            $table->dropUnique('iso_proveedor_seleccion_unica');
        });
    }
};
