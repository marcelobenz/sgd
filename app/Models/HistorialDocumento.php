<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialDocumento extends Model
{
    protected $fillable = ['id_documento', 'user_id', 'accion', 'notas'];

    public function documento()
    {
        return $this->belongsTo(Documento::class, 'id_documento');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'id_usr_creador');
    }

    public function ultimaModificacion()
    {
        return $this->belongsTo(User::class, 'id_usr_ultima_modif');
    }
    

}
