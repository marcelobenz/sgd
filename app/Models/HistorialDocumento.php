<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialDocumento extends Model
{
    protected $table = 'historial_documentos'; // si tu tabla se llama así

    protected $fillable = [
        'id_documento',
        'path',
        'titulo',
        'contenido',
        'estado',
        'version',
        'id_categoria',
        'id_usr_creador',
        'id_usr_ultima_modif',
        'id_usr_aprobador',
        'fecha_aprobacion',
    ];

    protected $casts = [
        'fecha_aprobacion' => 'datetime',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function documento()
    {
        return $this->belongsTo(Documento::class, 'id_documento');
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

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'id_usr_aprobador');
    }
}
