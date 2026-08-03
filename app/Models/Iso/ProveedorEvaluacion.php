<?php

namespace App\Models\Iso;

use App\Models\Documento;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProveedorEvaluacion extends Model
{
    protected $table = 'iso_proveedor_evaluaciones';
    protected $fillable = ['proveedor_id', 'periodo_id', 'fecha_evaluacion', 'tipo', 'evaluacion_anterior_id', 'calificaciones', 'puntaje', 'resultado', 'decision', 'estado_ciclo', 'conclusion', 'justificacion', 'proxima_evaluacion', 'requiere_analisis_riesgo', 'documento_id', 'enlace_externo', 'evaluado_por', 'importacion_lote'];
    protected $casts = ['fecha_evaluacion' => 'date', 'proxima_evaluacion' => 'date', 'calificaciones' => 'array', 'puntaje' => 'decimal:2', 'requiere_analisis_riesgo' => 'boolean'];
    public function proveedor() { return $this->belongsTo(Proveedor::class); }
    public function periodo() { return $this->belongsTo(Periodo::class); }
    public function evaluador() { return $this->belongsTo(User::class, 'evaluado_por'); }
    public function documento() { return $this->belongsTo(Documento::class); }
    public function riesgos() { return $this->belongsToMany(Riesgo::class, 'iso_proveedor_evaluacion_riesgo', 'evaluacion_id', 'riesgo_id'); }
    public function acciones() { return $this->hasMany(ProveedorAccion::class, 'evaluacion_id'); }
    public function evaluacionAnterior() { return $this->belongsTo(self::class, 'evaluacion_anterior_id'); }
    public function reevaluaciones() { return $this->hasMany(self::class, 'evaluacion_anterior_id'); }
}
