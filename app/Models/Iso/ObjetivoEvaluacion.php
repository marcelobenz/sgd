<?php

namespace App\Models\Iso;

use App\Models\Documento;
use App\Models\Iso\Concerns\RegistraCambiosIso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ObjetivoEvaluacion extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_objetivo_evaluaciones';
    protected $fillable = ['objetivo_id', 'fecha_evaluacion', 'resultado', 'cumplimiento', 'conclusion', 'justificacion', 'decision', 'proxima_evaluacion', 'documento_id', 'enlace_externo', 'evaluado_por'];
    protected $casts = ['fecha_evaluacion' => 'date', 'proxima_evaluacion' => 'date', 'resultado' => 'decimal:4'];

    public function objetivo() { return $this->belongsTo(Objetivo::class); }
    public function documento() { return $this->belongsTo(Documento::class); }
    public function evaluadoPor() { return $this->belongsTo(User::class, 'evaluado_por'); }
}
