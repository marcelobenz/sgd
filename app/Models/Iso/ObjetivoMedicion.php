<?php

namespace App\Models\Iso;

use App\Models\Documento;
use App\Models\Iso\Concerns\RegistraCambiosIso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ObjetivoMedicion extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_objetivo_mediciones';
    protected $fillable = ['indicador_id', 'fecha_medicion', 'periodo_referencia', 'valor', 'observaciones', 'documento_id', 'enlace_externo', 'registrado_por'];
    protected $casts = ['fecha_medicion' => 'date', 'valor' => 'decimal:4'];

    public function indicador() { return $this->belongsTo(ObjetivoIndicador::class, 'indicador_id'); }
    public function documento() { return $this->belongsTo(Documento::class); }
    public function registradoPor() { return $this->belongsTo(User::class, 'registrado_por'); }
}
