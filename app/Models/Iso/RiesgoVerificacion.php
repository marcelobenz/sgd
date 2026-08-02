<?php

namespace App\Models\Iso;

use App\Models\Documento;
use App\Models\Iso\Concerns\RegistraCambiosIso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RiesgoVerificacion extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_riesgo_verificaciones';
    protected $fillable = ['riesgo_id', 'tipo', 'fecha', 'eficacia', 'conclusion', 'impacto', 'probabilidad', 'indice', 'estado_resultante', 'justificacion_excepcion', 'documento_id', 'enlace_externo', 'verificado_por'];
    protected $casts = ['fecha' => 'date'];

    public function riesgo() { return $this->belongsTo(Riesgo::class); }
    public function verificador() { return $this->belongsTo(User::class, 'verificado_por'); }
    public function documento() { return $this->belongsTo(Documento::class); }
}
