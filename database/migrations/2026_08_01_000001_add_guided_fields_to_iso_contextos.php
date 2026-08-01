<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iso_contextos', function (Blueprint $table) {
            $table->string('fuente_tipo')->nullable()->after('fuente');
            $table->string('referencia_tipo')->nullable()->after('referencia_existente');
        });
    }

    public function down(): void
    {
        Schema::table('iso_contextos', function (Blueprint $table) {
            $table->dropColumn(['fuente_tipo', 'referencia_tipo']);
        });
    }
};
