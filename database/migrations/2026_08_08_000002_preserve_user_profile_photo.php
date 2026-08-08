<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_foto_path')->nullable()->after('avatar_valor');
        });

        DB::table('users')
            ->where('avatar_tipo', 'personalizado')
            ->whereNotNull('avatar_valor')
            ->update([
                'avatar_foto_path' => DB::raw('avatar_valor'),
                'avatar_valor' => null,
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('avatar_tipo', 'personalizado')
            ->whereNotNull('avatar_foto_path')
            ->update(['avatar_valor' => DB::raw('avatar_foto_path')]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_foto_path');
        });
    }
};
