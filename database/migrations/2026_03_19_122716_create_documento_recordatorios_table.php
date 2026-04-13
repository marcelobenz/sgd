<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentoRecordatoriosTable extends Migration
{
    public function up()
    {
        Schema::create('documento_recordatorios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id');
            $table->string('nombre');
            $table->text('mensaje')->nullable();

            $table->enum('frecuencia', ['diario', 'semanal', 'mensual', 'anual']);

            $table->date('fecha_inicio');
            $table->time('hora_envio')->nullable();

            $table->unsignedTinyInteger('dia_semana')->nullable(); // 1=lunes ... 7=domingo
            $table->unsignedTinyInteger('dia_mes')->nullable();    // 1 a 31
            $table->unsignedTinyInteger('mes_anual')->nullable();  // 1 a 12

            $table->boolean('notificar_interno')->default(true);
            $table->boolean('notificar_email')->default(false);
            $table->boolean('activo')->default(true);

            $table->dateTime('proxima_ejecucion')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->foreign('documento_id')
                ->references('id')
                ->on('documentos')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('documento_recordatorios');
    }
}