<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VacacionesSaldo extends Model
{
    protected $table = 'vacaciones_saldos';

    protected $fillable = ['user_id', 'anio', 'dias_pendientes', 'observaciones'];

    protected $casts = [
        'anio' => 'integer',
        'dias_pendientes' => 'integer',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
