<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacaciones_saldos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->unsignedSmallInteger('dias_pendientes')->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacaciones_saldos');
    }
};
