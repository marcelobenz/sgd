<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('preferencias')->nullable()->after('habilitado');
            $table->string('avatar_tipo', 20)->default('iniciales')->after('preferencias');
            $table->string('avatar_valor')->nullable()->after('avatar_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['preferencias', 'avatar_tipo', 'avatar_valor']);
        });
    }
};
