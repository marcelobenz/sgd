<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordatorioEjecucion extends Model
{
    protected $table = 'recordatorio_ejecuciones';

    protected $fillable = [
        'documento_recordatorio_id',
        'documento_id',
        'user_id',
        'resuelto_por_user_id',
        'fecha_programada',
        'estado',
        'fecha_resolucion',
        'postergado_hasta',
        'observacion',
        'observacion_resolucion',
    ];

    protected $casts = [
        'fecha_programada' => 'datetime',
        'fecha_resolucion' => 'datetime',
        'postergado_hasta' => 'datetime',
    ];

    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recordatorio()
    {
        return $this->belongsTo(DocumentoRecordatorio::class, 'documento_recordatorio_id');
    }

    public function resueltoPor()
    {
        return $this->belongsTo(User::class, 'resuelto_por_user_id');
    }

    public function revision()
    {
        return $this->hasOne(DocumentoRevision::class, 'recordatorio_ejecucion_id');
    }

}