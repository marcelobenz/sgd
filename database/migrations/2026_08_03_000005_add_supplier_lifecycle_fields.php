<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iso_proveedores', function (Blueprint $table) {
            $table->dateTime('fecha_baja')->nullable()->after('estado');
            $table->text('motivo_baja')->nullable()->after('fecha_baja');
            $table->dateTime('fecha_reactivacion')->nullable()->after('motivo_baja');
            $table->text('motivo_reactivacion')->nullable()->after('fecha_reactivacion');
        });
    }

    public function down(): void
    {
        Schema::table('iso_proveedores', function (Blueprint $table) {
            $table->dropColumn(['fecha_baja', 'motivo_baja', 'fecha_reactivacion', 'motivo_reactivacion']);
        });
    }
};
