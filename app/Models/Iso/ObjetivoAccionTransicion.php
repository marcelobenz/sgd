<?php

namespace App\Models\Iso;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ObjetivoAccionTransicion extends Model
{
    protected $table = 'iso_objetivo_accion_transiciones';
    protected $fillable = ['accion_id', 'estado_anterior', 'estado_nuevo', 'motivo', 'user_id'];

    public function accion() { return $this->belongsTo(ObjetivoAccion::class, 'accion_id'); }
    public function usuario() { return $this->belongsTo(User::class, 'user_id'); }
}
