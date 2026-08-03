<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('iso_proveedor_acciones')
            ->whereIn('evaluacion_id', function ($query) {
                $query->select('id')
                    ->from('iso_proveedor_evaluaciones')
                    ->where('resultado', 'aprobado');
            })
            ->update(['obligatoria' => false]);
    }

    public function down(): void
    {
        // La obligatoriedad histórica no puede reconstruirse de forma inequívoca.
    }
};
