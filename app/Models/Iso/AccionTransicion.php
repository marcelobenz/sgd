<?php

namespace App\Models\Iso;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AccionTransicion extends Model
{
    protected $table = 'iso_accion_transiciones';
    protected $fillable = ['accion_id', 'accion', 'estado_anterior', 'estado_nuevo', 'motivo', 'realizado_por'];

    public function accionRelacionada() { return $this->belongsTo(Accion::class, 'accion_id'); }
    public function realizadoPor() { return $this->belongsTo(User::class, 'realizado_por'); }
}
