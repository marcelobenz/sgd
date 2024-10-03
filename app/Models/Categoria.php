<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = ['nombre_categoria', 'parent_id'];

    // Relación para las subcategorías (hijas)
    public function subcategorias()
    {
        return $this->hasMany(Categoria::class, 'parent_id')->orderBy('nombre_categoria', 'asc');
    }

    // Relación para la categoría padre
    public function parent()
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }

    // Modificación para evitar eliminar categorías que tienen documentos o historial asociados
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($categoria) {
            if ($categoria->documentos()->exists() || $categoria->historialDocumentos()->exists()) {
                throw new \Exception('No se puede eliminar esta categoría porque tiene documentos o registros de historial asociados.');
            }

            if ($categoria->subcategorias()->exists()) {
                throw new \Exception('No se puede eliminar esta categoría porque tiene subcategorías asociadas.');
            }
        });
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'id_categoria');
    }

    public function historialDocumentos()
    {
        return $this->hasManyThrough(HistorialDocumento::class, Documento::class, 'id_categoria', 'id_documento');
    }


}
