<?php

namespace App\Models\Iso;

use App\Models\Documento;
use App\Models\Iso\Concerns\RegistraCambiosIso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ObjetivoAccion extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_objetivo_acciones';
    protected $fillable = ['objetivo_id', 'descripcion', 'area_responsable', 'responsable_id', 'fecha_objetivo', 'estado', 'resultado', 'documento_id', 'enlace_externo', 'cerrada_en', 'creado_por', 'actualizado_por'];
    protected $casts = ['fecha_objetivo' => 'date', 'cerrada_en' => 'datetime'];

    public function objetivo() { return $this->belongsTo(Objetivo::class); }
    public function responsable() { return $this->belongsTo(User::class, 'responsable_id'); }
    public function documento() { return $this->belongsTo(Documento::class); }
    public function seguimientos() { return $this->hasMany(ObjetivoAccionSeguimiento::class, 'accion_id'); }
    public function transiciones() { return $this->hasMany(ObjetivoAccionTransicion::class, 'accion_id'); }
}
