<?php

namespace App\Models\Iso;

use App\Models\Iso\Concerns\RegistraCambiosIso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Contexto extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_contextos';
    protected $fillable = ['periodo_id', 'tipo', 'numero', 'codigo', 'titulo', 'descripcion', 'proceso', 'fuente_tipo', 'fuente', 'fecha_identificacion', 'responsable_id', 'relevante_sgc', 'decision', 'justificacion', 'referencia_tipo', 'referencia_existente', 'estado', 'creado_por', 'actualizado_por'];
    protected $casts = ['fecha_identificacion' => 'date', 'relevante_sgc' => 'boolean'];

    public function periodo() { return $this->belongsTo(Periodo::class); }
    public function responsable() { return $this->belongsTo(User::class, 'responsable_id'); }
    public function riesgos() { return $this->hasMany(Riesgo::class); }

    public function getTipoEtiquetaAttribute(): string
    {
        return ucfirst($this->tipo);
    }
}
