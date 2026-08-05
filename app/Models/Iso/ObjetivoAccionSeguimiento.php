<?php

namespace App\Models\Iso;

use App\Models\Documento;
use App\Models\Iso\Concerns\RegistraCambiosIso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ObjetivoAccionSeguimiento extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_objetivo_accion_seguimientos';
    protected $fillable = ['accion_id', 'fecha', 'detalle', 'documento_id', 'enlace_externo', 'registrado_por'];
    protected $casts = ['fecha' => 'date'];

    public function accion() { return $this->belongsTo(ObjetivoAccion::class, 'accion_id'); }
    public function documento() { return $this->belongsTo(Documento::class); }
    public function registradoPor() { return $this->belongsTo(User::class, 'registrado_por'); }
}
