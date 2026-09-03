<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('fecha_ingreso')->nullable()->after('habilitado');
            $table->string('area')->nullable()->after('fecha_ingreso');
            $table->foreignId('jefe_id')->nullable()->after('area')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['jefe_id']);
            $table->dropColumn(['fecha_ingreso', 'area', 'jefe_id']);
        });
    }
};
