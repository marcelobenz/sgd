<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iso_periodo_transiciones', function (Blueprint $table) {
            $table->string('accion')->change();
        });
    }

    public function down(): void
    {
        // Se conserva como string para no destruir transiciones de activación ya auditadas.
    }
};
