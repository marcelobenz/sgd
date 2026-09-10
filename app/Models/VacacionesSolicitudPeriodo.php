<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VacacionesSolicitudPeriodo extends Model
{
    protected $table = 'vacaciones_solicitud_periodos';

    protected $fillable = ['vacaciones_solicitud_id', 'anio', 'dias'];

    protected $casts = [
        'anio' => 'integer',
        'dias' => 'integer',
    ];

    public function solicitud()
    {
        return $this->belongsTo(VacacionesSolicitud::class, 'vacaciones_solicitud_id');
    }
}
