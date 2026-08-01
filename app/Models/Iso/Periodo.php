<?php

namespace App\Models\Iso;

use App\Models\Iso\Concerns\RegistraCambiosIso;
use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_periodos';
    protected $fillable = ['anio', 'nombre', 'estado', 'cambio_climatico_relevante', 'fundamento_cambio_climatico', 'creado_por', 'cerrado_por', 'cerrado_en'];
    protected $casts = ['cambio_climatico_relevante' => 'boolean', 'cerrado_en' => 'datetime'];

    public function contextos() { return $this->hasMany(Contexto::class); }
    public function riesgos() { return $this->hasMany(Riesgo::class); }
    public function evaluacionesPartes() { return $this->hasMany(ParteInteresadaEvaluacion::class); }
    public function transiciones() { return $this->hasMany(PeriodoTransicion::class); }
}
