<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iso_seguimientos', function (Blueprint $table) {
            $table->enum('tipo', ['seguimiento', 'evidencia_complementaria'])->default('seguimiento')->after('accion_id');
        });
    }

    public function down(): void
    {
        Schema::table('iso_seguimientos', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
