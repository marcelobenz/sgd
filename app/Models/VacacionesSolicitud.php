<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VacacionesSolicitud extends Model
{
    protected $table = 'vacaciones_solicitudes';

    protected $fillable = [
        'user_id', 'creada_por', 'fecha_desde', 'fecha_hasta', 'dias',
        'observaciones', 'estado', 'revisada_por', 'revisada_at', 'motivo_rechazo',
    ];

    protected $casts = [
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'revisada_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creada_por');
    }

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisada_por');
    }

    public function periodos()
    {
        return $this->hasMany(VacacionesSolicitudPeriodo::class, 'vacaciones_solicitud_id');
    }
}
