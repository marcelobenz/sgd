<?php

namespace App\Models\Iso;

use App\Models\Iso\Concerns\RegistraCambiosIso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Objetivo extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_objetivos';
    protected $fillable = ['periodo_id', 'numero', 'codigo', 'compromiso_politica', 'proceso', 'titulo', 'descripcion', 'area_responsable', 'responsable_id', 'fecha_inicio', 'fecha_objetivo', 'periodicidad_seguimiento', 'estado', 'observaciones', 'creado_por', 'actualizado_por'];
    protected $casts = ['fecha_inicio' => 'date', 'fecha_objetivo' => 'date'];

    public function periodo() { return $this->belongsTo(Periodo::class); }
    public function responsable() { return $this->belongsTo(User::class, 'responsable_id'); }
    public function indicadores() { return $this->hasMany(ObjetivoIndicador::class); }
    public function indicadorPrincipal() { return $this->hasOne(ObjetivoIndicador::class)->where('principal', true); }
    public function acciones() { return $this->hasMany(ObjetivoAccion::class); }
    public function evaluaciones() { return $this->hasMany(ObjetivoEvaluacion::class); }
    public function revisiones() { return $this->hasMany(ObjetivoRevision::class); }
    public function contextos() { return $this->belongsToMany(Contexto::class, 'iso_objetivo_contexto', 'objetivo_id', 'contexto_id'); }
    public function riesgos() { return $this->belongsToMany(Riesgo::class, 'iso_objetivo_riesgo', 'objetivo_id', 'riesgo_id'); }
    public function partes() { return $this->belongsToMany(ParteInteresada::class, 'iso_objetivo_parte', 'objetivo_id', 'parte_id'); }

    public function tieneActividadGestion(): bool
    {
        if ($this->evaluaciones()->exists() || $this->indicadores()->whereHas('mediciones')->exists()) return true;

        return $this->acciones()->where(function ($query) {
            $query->where('estado', '!=', 'pendiente')
                ->orWhereNotNull('resultado')
                ->orWhereNotNull('documento_id')
                ->orWhereNotNull('enlace_externo')
                ->orWhereHas('seguimientos')
                ->orWhereHas('transiciones');
        })->exists();
    }
}
