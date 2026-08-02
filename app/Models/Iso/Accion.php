<?php

namespace App\Models\Iso;

use App\Models\Iso\Concerns\RegistraCambiosIso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Accion extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_acciones';
    protected $fillable = ['riesgo_id', 'descripcion', 'responsable_id', 'fecha_objetivo', 'estado', 'resultado', 'creado_por', 'actualizado_por', 'completada_en'];
    protected $casts = ['fecha_objetivo' => 'date', 'completada_en' => 'datetime'];

    public function riesgo() { return $this->belongsTo(Riesgo::class); }
    public function responsable() { return $this->belongsTo(User::class, 'responsable_id'); }
    public function seguimientos() { return $this->hasMany(Seguimiento::class); }
    public function transiciones() { return $this->hasMany(AccionTransicion::class); }
}
