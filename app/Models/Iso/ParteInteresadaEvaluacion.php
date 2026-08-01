<?php

namespace App\Models\Iso;

use App\Models\Documento;
use App\Models\Iso\Concerns\RegistraCambiosIso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ParteInteresadaEvaluacion extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_parte_evaluaciones';
    protected $fillable = ['parte_id', 'periodo_id', 'fecha_evaluacion', 'resultado', 'observaciones', 'proxima_revision', 'documento_id', 'enlace_externo', 'evaluado_por'];
    protected $casts = ['fecha_evaluacion' => 'date', 'proxima_revision' => 'date'];

    public function parte() { return $this->belongsTo(ParteInteresada::class, 'parte_id'); }
    public function periodo() { return $this->belongsTo(Periodo::class); }
    public function documento() { return $this->belongsTo(Documento::class); }
    public function evaluador() { return $this->belongsTo(User::class, 'evaluado_por'); }
    public function riesgos() { return $this->belongsToMany(Riesgo::class, 'iso_parte_evaluacion_riesgo', 'evaluacion_id', 'riesgo_id'); }
}
