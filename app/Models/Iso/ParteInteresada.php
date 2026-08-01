<?php

namespace App\Models\Iso;

use App\Models\Iso\Concerns\RegistraCambiosIso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ParteInteresada extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_partes';
    protected $fillable = ['clave', 'nombre', 'pertinente_sgc', 'necesidades_requisitos', 'area_responsable', 'responsable_id', 'metodo_medicion', 'proceso', 'requisitos_climaticos', 'detalle_climatico', 'estado', 'creado_por', 'actualizado_por'];
    protected $casts = ['pertinente_sgc' => 'boolean', 'requisitos_climaticos' => 'boolean'];

    public function responsable() { return $this->belongsTo(User::class, 'responsable_id'); }
    public function evaluaciones() { return $this->hasMany(ParteInteresadaEvaluacion::class, 'parte_id'); }
    public function ultimaEvaluacion() { return $this->hasOne(ParteInteresadaEvaluacion::class, 'parte_id')->latestOfMany('fecha_evaluacion'); }
}
