<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoRevision extends Model
{
    protected $table = 'documento_revisiones';

    protected $fillable = [
        'documento_id',
        'recordatorio_id',
        'recordatorio_ejecucion_id',
        'user_id',
        'fecha_revision',
        'resultado',
        'observacion',
        'requiere_nueva_version',
        'version_generada_id',
        'archivo_evidencia',
    ];

    protected $casts = [
        'fecha_revision' => 'datetime',
        'requiere_nueva_version' => 'boolean',
    ];

    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function ejecucion()
    {
        return $this->belongsTo(RecordatorioEjecucion::class, 'recordatorio_ejecucion_id');
    }

    public function recordatorio()
    {
        return $this->belongsTo(DocumentoRecordatorio::class, 'recordatorio_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}