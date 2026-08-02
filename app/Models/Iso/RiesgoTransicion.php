<?php

namespace App\Models\Iso;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RiesgoTransicion extends Model
{
    protected $table = 'iso_riesgo_transiciones';
    protected $fillable = ['riesgo_id', 'accion', 'estado_anterior', 'estado_nuevo', 'motivo', 'realizado_por'];

    public function riesgo() { return $this->belongsTo(Riesgo::class); }
    public function realizadoPor() { return $this->belongsTo(User::class, 'realizado_por'); }
}
