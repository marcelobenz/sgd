<?php

namespace App\Models\Iso;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PeriodoTransicion extends Model
{
    protected $table = 'iso_periodo_transiciones';
    protected $fillable = ['periodo_id', 'accion', 'estado_anterior', 'estado_nuevo', 'motivo', 'resumen_control', 'realizado_por'];
    protected $casts = ['resumen_control' => 'array'];

    public function periodo() { return $this->belongsTo(Periodo::class); }
    public function realizadoPor() { return $this->belongsTo(User::class, 'realizado_por'); }
}
