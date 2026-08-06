<?php

namespace App\Models\Iso;

use App\Models\Iso\Concerns\RegistraCambiosIso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Riesgo extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_riesgos';
    protected $fillable = ['periodo_id', 'riesgo_origen_id', 'contexto_id', 'numero', 'codigo', 'tipo', 'proceso', 'identificacion', 'partes_interesadas', 'efecto_potencial', 'criterio_eficacia', 'impacto_inicial', 'probabilidad_inicial', 'indice_inicial', 'responsable_id', 'fecha_verificacion_prevista', 'eficacia', 'conclusion_eficacia', 'impacto_final', 'probabilidad_final', 'indice_final', 'estado', 'creado_por', 'actualizado_por', 'finalizado_en'];
    protected $casts = ['fecha_verificacion_prevista' => 'date', 'finalizado_en' => 'datetime'];

    public function periodo() { return $this->belongsTo(Periodo::class); }
    public function origenContinuidad() { return $this->belongsTo(Riesgo::class, 'riesgo_origen_id'); }
    public function continuidades() { return $this->hasMany(Riesgo::class, 'riesgo_origen_id'); }
    public function contexto() { return $this->belongsTo(Contexto::class); }
    public function responsable() { return $this->belongsTo(User::class, 'responsable_id'); }
    public function acciones() { return $this->hasMany(Accion::class); }
    public function verificaciones() { return $this->hasMany(RiesgoVerificacion::class); }
    public function transiciones() { return $this->hasMany(RiesgoTransicion::class); }
    public function evaluacionesProveedor() { return $this->belongsToMany(ProveedorEvaluacion::class, 'iso_proveedor_evaluacion_riesgo', 'riesgo_id', 'evaluacion_id'); }
}
