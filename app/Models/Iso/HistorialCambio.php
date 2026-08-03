<?php

namespace App\Models\Iso;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HistorialCambio extends Model
{
    protected $table = 'iso_historial_cambios';

    protected $fillable = ['entidad_tipo', 'entidad_id', 'evento', 'valores_anteriores', 'valores_nuevos', 'user_id'];

    protected $casts = ['valores_anteriores' => 'array', 'valores_nuevos' => 'array'];

    public function usuario() { return $this->belongsTo(User::class, 'user_id'); }
}
