<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iso_partes', function (Blueprint $table) {
            $table->string('area_responsable')->nullable()->after('necesidades_requisitos');
        });

        DB::table('iso_partes')->whereNull('area_responsable')->whereNotNull('responsable_id')->orderBy('id')->eachById(function ($parte) {
            $nombre = DB::table('users')->where('id', $parte->responsable_id)->value('name');
            DB::table('iso_partes')->where('id', $parte->id)->update(['area_responsable' => $nombre ? "Pendiente de definir (antes: {$nombre})" : 'Pendiente de definir']);
        });
    }

    public function down(): void
    {
        Schema::table('iso_partes', function (Blueprint $table) {
            $table->dropColumn('area_responsable');
        });
    }
};
