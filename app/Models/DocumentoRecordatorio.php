<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoRecordatorio extends Model
{
    protected $table = 'documento_recordatorios';

    protected $fillable = [
        'documento_id',
        'nombre',
        'mensaje',
        'frecuencia',
        'fecha_inicio',
        'hora_envio',
        'dia_semana',
        'dia_mes',
        'mes_anual',
        'notificar_interno',
        'notificar_email',
        'activo',
        'proxima_ejecucion',
        'created_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'proxima_ejecucion' => 'datetime',
        'notificar_interno' => 'boolean',
        'notificar_email' => 'boolean',
        'activo' => 'boolean',
    ];

    public function documento()
    {
        return $this->belongsTo(Documento::class, 'documento_id');
    }

    public function usuarios()
    {
        return $this->belongsToMany(
            User::class,
            'documento_recordatorio_user',
            'documento_recordatorio_id',
            'user_id'
        )->withTimestamps();
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ejecuciones()
    {
        return $this->hasMany(RecordatorioEjecucion::class, 'documento_recordatorio_id');
    }
}