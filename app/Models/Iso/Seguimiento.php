<?php

namespace App\Models\Iso;

use App\Models\Documento;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Seguimiento extends Model
{
    protected $table = 'iso_seguimientos';
    protected $fillable = ['accion_id', 'tipo', 'fecha', 'detalle', 'resultado', 'documento_id', 'enlace_externo', 'registrado_por'];
    protected $casts = ['fecha' => 'date'];

    public function accion() { return $this->belongsTo(Accion::class); }
    public function documento() { return $this->belongsTo(Documento::class); }
    public function usuario() { return $this->belongsTo(User::class, 'registrado_por'); }
}
