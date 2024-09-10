<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdUsrAprobadorToDocumentosAndHistorialDocumentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usr_aprobador')->nullable()->after('updated_at'); // Ajusta la columna donde quieras agregar el nuevo campo
            $table->foreign('id_usr_aprobador')->references('id')->on('users'); // Relación con la tabla de usuarios
        });

        Schema::table('historial_documentos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usr_aprobador')->nullable()->after('updated_at'); // Ajusta la columna donde quieras agregar el nuevo campo
            $table->foreign('id_usr_aprobador')->references('id')->on('users'); // Relación con la tabla de usuarios
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropForeign(['id_usr_aprobador']);
            $table->dropColumn('id_usr_aprobador');
        });

        Schema::table('historial_documentos', function (Blueprint $table) {
            $table->dropForeign(['id_usr_aprobador']);
            $table->dropColumn('id_usr_aprobador');
        });
    }
}
