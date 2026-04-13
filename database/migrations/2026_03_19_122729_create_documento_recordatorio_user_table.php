<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentoRecordatorioUserTable extends Migration
{
    public function up()
    {
        Schema::create('documento_recordatorio_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_recordatorio_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('documento_recordatorio_id')
                ->references('id')
                ->on('documento_recordatorios')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique(['documento_recordatorio_id', 'user_id'], 'doc_recordatorio_user_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('documento_recordatorio_user');
    }
}