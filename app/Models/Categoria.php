<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Para evitar que se eliminen categorias que existan en documento o historial
class Categoria extends Model
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($categoria) {
            if ($categoria->documentos()->exists() || $categoria->historialDocumentos()->exists()) {
                throw new \Exception('No se puede eliminar esta categoría porque tiene documentos o registros de historial asociados.');
            }
        });
    }

}
